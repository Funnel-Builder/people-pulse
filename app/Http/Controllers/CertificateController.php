<?php

namespace App\Http\Controllers;

use App\Models\CertificateRequest;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService
    ) {
    }

    /**
     * Display the certificate request form.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $forceNew = $request->query('new') == '1';

        // Check for existing pending or authorized request
        $activeRequest = CertificateRequest::where('user_id', $user->id)
            ->whereIn('status', [
                CertificateRequest::STATUS_PENDING,
                CertificateRequest::STATUS_AUTHORIZED,
            ])
            ->first();

        // Get latest issued certificate (if no active request and not forcing new)
        $latestIssuedCertificate = null;
        if (!$activeRequest && !$forceNew) {
            $latestIssuedCertificate = CertificateRequest::where('user_id', $user->id)
                ->where('status', CertificateRequest::STATUS_ISSUED)
                ->latest('issued_at')
                ->first();
        }

        return Inertia::render('services/CertificateRequest', [
            'purposes' => config('services_module.certificate_purposes', []),
            'employeeInfo' => $this->certificateService->getEmployeeInfo($user),
            'activeRequest' => $activeRequest,
            'latestIssuedCertificate' => $latestIssuedCertificate,
            'issuerInfo' => config('services_module.issuer'),
            'companyInfo' => config('services_module.company'),
        ]);
    }

    /**
     * Store a new certificate request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'purpose' => 'required|string',
            'purpose_other' => 'nullable|string|max:255',
            'urgency' => 'required|in:normal,urgent',
            'remarks' => 'nullable|string|max:1000',
            'agreement' => 'required|accepted',
        ]);

        $this->certificateService->createRequest($request->user(), $validated);

        return redirect()->route('services.certificate')
            ->with('success', 'Certificate request submitted successfully.');
    }

    /**
     * Cancel a certificate request.
     */
    public function cancel(Request $request, CertificateRequest $certificateRequest)
    {
        $user = $request->user();

        if ($certificateRequest->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        if ($certificateRequest->status !== 'pending') {
            abort(400, 'Only pending requests can be cancelled.');
        }

        $certificateRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'Certificate request cancelled successfully.');
    }

    /**
     * Display the user's certificate request history.
     */
    public function history(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('services/CertificateHistory', [
            'requests' => $this->certificateService->getUserRequests($user),
            'purposes' => config('services_module.certificate_purposes', []),
        ]);
    }

    /**
     * Display the approvals list for managers/admins.
     */
    public function approvals(Request $request): Response
    {
        $user = $request->user();

        if (!$user->isManager() && !$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $isHistory = $request->query('history') == 1;

        if ($isHistory) {
            $requests = $this->certificateService->getApprovalHistory($user);
        } else {
            $requests = $this->certificateService->getPendingApprovals($user);
        }

        return Inertia::render('services/CertificateApprovals', [
            'requests' => $requests,
            'userRole' => $user->role,
            'isHistory' => $isHistory,
        ]);
    }

    /**
     * Display the review page for a specific request.
     */
    public function review(Request $request, CertificateRequest $certificateRequest): Response
    {
        $user = $request->user();

        // Use canView to allow viewing even after certificate is issued (for modal)
        if (!$this->certificateService->canView($user, $certificateRequest)) {
            abort(403, 'Unauthorized access.');
        }

        $certificateRequest->load(['user.department', 'user.subDepartment']);

        return Inertia::render('services/CertificateReview', [
            'request' => $certificateRequest,
            'employeeInfo' => $this->certificateService->getEmployeeInfo($certificateRequest->user),
            'issuerInfo' => config('services_module.issuer'),
            'companyInfo' => config('services_module.company'),
        ]);
    }

    /**
     * Authorize the certificate request (Manager action).
     */
    public function authorizeRequest(Request $request, CertificateRequest $certificateRequest)
    {
        $user = $request->user();

        if (!$user->isManager()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$certificateRequest->isPending()) {
            abort(400, 'Request cannot be authorized.');
        }

        $this->certificateService->authorizeRequest($certificateRequest, $user);

        return redirect()->route('services.certificate.approvals')
            ->with('success', 'Certificate request authorized and sent to admin.');
    }

    /**
     * Issue the certificate (Admin only).
     */
    public function issue(Request $request, CertificateRequest $certificateRequest)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            abort(403, 'Only admins can issue certificates.');
        }

        $this->certificateService->issueRequest($certificateRequest, $user);

        return back()->with('success', 'Certificate issued successfully.');
    }

    /**
     * Download the certificate PDF.
     */
    public function download(Request $request, CertificateRequest $certificateRequest)
    {
        $user = $request->user();

        // Allow download if user owns the request OR is manager/admin
        $canDownload = $certificateRequest->user_id === $user->id
            || $user->isManager()
            || $user->isAdmin();

        if (!$canDownload) {
            abort(403, 'Unauthorized access.');
        }

        if (!$certificateRequest->isIssued()) {
            abort(400, 'Certificate has not been issued yet.');
        }

        $pdf = $this->certificateService->generateCertificatePdf($certificateRequest);
        $filename = "employment_certificate_{$certificateRequest->ref_id}.pdf";
        $filename = str_replace('/', '_', $filename);

        return $pdf->download($filename);
    }

    /**
     * Generate HTML preview for embedding.
     */
    public function preview(Request $request, CertificateRequest $certificateRequest)
    {
        $user = $request->user();

        // Allow preview for owner (if issued) or approvers
        $canPreview = ($certificateRequest->user_id === $user->id && $certificateRequest->isIssued())
            || $user->isManager()
            || $user->isAdmin();

        if (!$canPreview) {
            abort(403, 'Unauthorized access.');
        }

        $data = $this->certificateService->getCertificateData($certificateRequest);
        $data['isWebPreview'] = true;

        return view('pdf.certificate-preview', $data);
    }

    /**
     * Email the certificate.
     */
    public function email(Request $request, CertificateRequest $certificateRequest)
    {
        $user = $request->user();

        if (!$certificateRequest->isIssued()) {
            abort(400, 'Certificate has not been issued yet.');
        }

        $validated = $request->validate([
            'recipient' => 'required|in:employee,self',
        ]);

        $pdf = $this->certificateService->generateCertificatePdf($certificateRequest);
        $pdfContent = $pdf->output();

        $recipientEmail = $validated['recipient'] === 'employee'
            ? $certificateRequest->user->email
            : $user->email;

        $recipientName = $validated['recipient'] === 'employee'
            ? $certificateRequest->user->name
            : $user->name;

        // Send email with PDF attachment
        $htmlContent = "
            <p>Dear {$recipientName},</p>
            <p>Please find attached your Employment Certificate (Ref: {$certificateRequest->ref_id}).</p>
            <p>Best regards,<br>People & Operations Team</p>
        ";

        Mail::html($htmlContent, function ($message) use ($recipientEmail, $recipientName, $certificateRequest, $pdfContent) {
            $message->to($recipientEmail, $recipientName)
                ->subject('Employment Certificate - ' . $certificateRequest->ref_id)
                ->attachData(
                    $pdfContent,
                    'employment_certificate_' . str_replace('/', '_', $certificateRequest->ref_id) . '.pdf',
                    ['mime' => 'application/pdf']
                );
        });

        return back()->with('success', 'Certificate emailed successfully.');
    }

    /**
     * Reject a certificate request.
     */
    public function reject(Request $request, CertificateRequest $certificateRequest)
    {
        $user = $request->user();

        if (!$this->certificateService->canApprove($user, $certificateRequest)) {
            abort(403, 'Unauthorized access.');
        }

        $this->certificateService->rejectRequest($certificateRequest, $user);

        return redirect()->route('services.certificate.approvals')
            ->with('success', 'Certificate request rejected.');
    }

    /**
     * Request missing information from the employee via email.
     */
    public function requestMissingInfo(Request $request, CertificateRequest $certificateRequest)
    {
        $user = $request->user();

        if (!$this->certificateService->canView($user, $certificateRequest)) {
            abort(403, 'Unauthorized access.');
        }

        $employee = $certificateRequest->user;
        $missingFields = [];

        if (empty($employee->fathers_name))
            $missingFields[] = "Father's Name";
        if (empty($employee->mothers_name))
            $missingFields[] = "Mother's Name";
        if (empty($employee->nid_number))
            $missingFields[] = "NID Number";
        // Check for address fields if they exist on the model, otherwise generic

        $listItems = count($missingFields) > 0
            ? implode("<br>", array_map(fn($f) => "[ ] $f", $missingFields))
            : "[ ] Father's Name<br>[ ] Mother's Name<br>[ ] NID Number<br>[ ] Permanent Address";

        $htmlContent = "
            <p>Dear {$employee->name},</p>
            <p>We are currently reviewing your request for an Employment Certificate (Ref: {$certificateRequest->ref_id}). However, we noticed that some information in your profile is incomplete, which is required for us to proceed.</p>
            <p>Please update the following details in your profile:</p>
            <div style='background: #f5f5f5; padding: 15px; border-radius: 4px; margin: 15px 0; font-family: monospace;'>{$listItems}</div>
            <p>Once you have updated this information, please let us know so we can finalize your certificate.</p>
            <p>Best regards,<br>People & Operations Team</p>
        ";

        Mail::html($htmlContent, function ($message) use ($employee, $certificateRequest) {
            $message->to($employee->email, $employee->name)
                ->subject("Action Required: Missing Information for Certificate Request ({$certificateRequest->ref_id})");
        });

        return back()->with('success', 'Missing information request sent to employee.');
    }
}

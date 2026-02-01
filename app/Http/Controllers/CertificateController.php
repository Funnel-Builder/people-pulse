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
    public function index(Request $request, ?string $type = 'employment_certificate'): Response
    {
        $user = $request->user();
        $forceNew = $request->query('new') == '1';

        // Check for existing pending or authorized request
        $activeRequest = CertificateRequest::where('user_id', $user->id)
            ->where('type', $type)
            ->whereIn('status', [
                CertificateRequest::STATUS_PENDING,
            ])
            ->first();

        // Get latest issued certificate (if no active request and not forcing new)
        $latestIssuedCertificate = null;
        if (!$activeRequest && !$forceNew) {
            $latestIssuedCertificate = CertificateRequest::where('user_id', $user->id)
                ->where('type', $type)
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
            'currentType' => $type,
        ]);
    }

    /**
     * Store a new certificate request.
     */
    public function store(Request $request)
    {
        $type = $request->input('type', 'employment_certificate');
        
        // Purpose is required only for employment_certificate
        $purposeRule = $type === 'employment_certificate' ? 'required|string' : 'nullable|string';
        
        $validated = $request->validate([
            'purpose' => $purposeRule,
            'type' => 'nullable|string|in:employment_certificate,visa_recommendation_letter,release_letter,experience_certificate',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'passport_number' => 'nullable|string|max:255',
            'passport_issue_date' => 'nullable|date',
            'passport_expiry_date' => 'nullable|date|after:passport_issue_date',
            'passport_issue_place' => 'nullable|string|max:255',
            'purpose_other' => 'nullable|string|max:255',
            'urgency' => 'required|in:normal,urgent',
            'remarks' => 'nullable|string|max:1000',
            'agreement' => 'required|accepted',
        ]);

        // Set default purpose for non-EC types if not provided
        if (empty($validated['purpose'])) {
            $defaultPurposes = [
                'visa_recommendation_letter' => 'visa_application',
                'release_letter' => 'resignation',
                'experience_certificate' => 'job_application',
            ];
            $validated['purpose'] = $defaultPurposes[$type] ?? 'general';
        }

        $this->certificateService->createRequest($request->user(), $validated);

        // Redirect to the correct type-specific route
        $routeMap = [
            'employment_certificate' => 'services.certificate',
            'visa_recommendation_letter' => 'services.certificate.visa',
            'release_letter' => 'services.certificate.release',
            'experience_certificate' => 'services.certificate.experience',
        ];

        return redirect()->route($routeMap[$type] ?? 'services.certificate')
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
    public function history(Request $request, ?string $type = 'employment_certificate'): Response
    {
        $user = $request->user();

        return Inertia::render('services/CertificateHistory', [
            'requests' => $this->certificateService->getUserRequests($user, $type),
            'purposes' => config('services_module.certificate_purposes', []),
            'currentType' => $type,
        ]);
    }

    /**
     * Display the approvals list for managers/admins.
     */
    public function approvals(Request $request): Response
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
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
    // Manager authorization method removed

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

        // Send plain text email to employee
        $recipientEmail = $certificateRequest->user->email;
        $recipientName = $certificateRequest->user->name;

        $htmlContent = "
            <p>Dear {$recipientName},</p>
            <p>Your Employment Certificate (Ref: {$certificateRequest->ref_id}) is ready.</p>
            <p>Please collect it physically from the HR department.</p>
            <p>Best regards,<br>People & Operations Team</p>
        ";

        try {
            Mail::html($htmlContent, function ($message) use ($recipientEmail, $recipientName) {
                $message->to($recipientEmail, $recipientName)
                    ->subject('Employment Certificate Ready for Collection');
            });
        } catch (\Exception $e) {
            // Log email error but don't fail the issuance
            \Log::error('Failed to send certificate readiness email: ' . $e->getMessage());
        }

        return back()->with('success', 'Certificate issued successfully.');
    }

    /**
     * Download the certificate PDF.
     */
    public function download(Request $request, CertificateRequest $certificateRequest)
    {
        $user = $request->user();

        // Allow download if user is admin
        $canDownload = $user->isAdmin();

        if (!$canDownload) {
            abort(403, 'Unauthorized access.');
        }

        if (!$certificateRequest->isIssued()) {
            abort(400, 'Certificate has not been issued yet.');
        }

        $pdf = $this->certificateService->generateCertificatePdf($certificateRequest);
        $filename = "{$certificateRequest->type}_{$certificateRequest->ref_id}.pdf";
        $filename = str_replace('/', '_', $filename);

        if ($request->has('view')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Generate HTML preview for embedding.
     */
    public function preview(Request $request, CertificateRequest $certificateRequest)
    {
        $user = $request->user();

        // Allow preview for owner (draft view) or admin
        $canPreview = ($certificateRequest->user_id === $user->id && $certificateRequest->isIssued())
            || $user->isAdmin();

        if (!$canPreview) {
            abort(403, 'Unauthorized access.');
        }

        $data = $this->certificateService->getCertificateData($certificateRequest);
        $data['isWebPreview'] = true;

        $view = match ($certificateRequest->type) {
            CertificateRequest::TYPE_VISA_RECOMMENDATION => 'pdf.visa-recommendation-letter',
            CertificateRequest::TYPE_RELEASE_LETTER => 'pdf.release-letter',
            CertificateRequest::TYPE_EXPERIENCE_CERTIFICATE => 'pdf.experience-certificate',
            default => 'pdf.certificate', // Retain original for EC
        };

        return view($view, $data);
    }

    /**
     * Email the certificate.
     */
    public function email(Request $request, CertificateRequest $certificateRequest)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

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

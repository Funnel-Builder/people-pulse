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

        return Inertia::render('services/CertificateRequest', [
            'purposes' => config('services_module.certificate_purposes', []),
            'employeeInfo' => $this->certificateService->getEmployeeInfo($user),
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

        $pendingRequests = $this->certificateService->getPendingApprovals($user);

        return Inertia::render('services/CertificateApprovals', [
            'requests' => $pendingRequests,
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
     * Issue the certificate.
     */
    public function issue(Request $request, CertificateRequest $certificateRequest)
    {
        $user = $request->user();

        if (!$this->certificateService->canApprove($user, $certificateRequest)) {
            abort(403, 'Unauthorized access.');
        }

        $this->certificateService->issueRequest($certificateRequest, $user);

        // Return back so the modal can appear on the same page
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
     * Generate PDF for preview (inline display).
     */
    public function preview(Request $request, CertificateRequest $certificateRequest)
    {
        $user = $request->user();

        // Allow preview for approvers during review
        $canPreview = $user->isManager() || $user->isAdmin();

        if (!$canPreview) {
            abort(403, 'Unauthorized access.');
        }

        $pdf = $this->certificateService->generateCertificatePdf($certificateRequest);

        return $pdf->stream("certificate_preview.pdf");
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
        Mail::send([], [], function ($message) use ($recipientEmail, $recipientName, $certificateRequest, $pdfContent) {
            $message->to($recipientEmail, $recipientName)
                ->subject('Employment Certificate - ' . $certificateRequest->ref_id)
                ->html("
                    <p>Dear {$recipientName},</p>
                    <p>Please find attached your Employment Certificate (Ref: {$certificateRequest->ref_id}).</p>
                    <p>Best regards,<br>People & Operations Team</p>
                ")
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
}

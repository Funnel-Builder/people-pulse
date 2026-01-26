<?php

namespace App\Services;

use App\Models\CertificateRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CertificateService
{
    /**
     * Create a new certificate request.
     */
    public function createRequest(User $user, array $data): CertificateRequest
    {
        return CertificateRequest::create([
            'ref_id' => CertificateRequest::generateRefId(),
            'user_id' => $user->id,
            'purpose' => $data['purpose'],
            'purpose_other' => $data['purpose_other'] ?? null,
            'urgency' => $data['urgency'] ?? 'normal',
            'remarks' => $data['remarks'] ?? null,
            'status' => CertificateRequest::STATUS_PENDING,
        ]);
    }

    /**
     * Get pending certificate requests for approval.
     * Managers see requests from their department/sub-department.
     * Admins see all pending requests.
     */
    public function getPendingApprovals(User $user): Collection
    {
        if (!$user->isManager() && !$user->isAdmin()) {
            return collect([]);
        }

        $query = CertificateRequest::with(['user.department', 'user.subDepartment'])
            ->where('status', CertificateRequest::STATUS_PENDING);

        // If manager, filter by managed sub-departments
        if ($user->isManager() && !$user->isAdmin()) {
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();

            if (!empty($managedSubDepartmentIds)) {
                $query->whereHas('user', function ($q) use ($managedSubDepartmentIds) {
                    $q->whereIn('sub_department_id', $managedSubDepartmentIds);
                });
            } else {
                // Manager without sub-departments - show department-level requests
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('department_id', $user->department_id);
                });
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get all certificate requests for admin (for records view).
     */
    public function getAllRequests(): Collection
    {
        return CertificateRequest::with(['user.department', 'user.subDepartment', 'issuer'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get certificate requests for a specific user.
     */
    public function getUserRequests(User $user): Collection
    {
        return CertificateRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Approve a certificate request.
     */
    public function approveRequest(CertificateRequest $request, User $approver): CertificateRequest
    {
        $request->update([
            'status' => CertificateRequest::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return $request->fresh();
    }

    /**
     * Issue a certificate (final step by admin/manager).
     */
    public function issueRequest(CertificateRequest $request, User $issuer): CertificateRequest
    {
        $request->update([
            'status' => CertificateRequest::STATUS_ISSUED,
            'issued_by' => $issuer->id,
            'issued_at' => now(),
            // If not already approved, also mark as approved
            'approved_by' => $request->approved_by ?? $issuer->id,
            'approved_at' => $request->approved_at ?? now(),
        ]);

        return $request->fresh();
    }

    /**
     * Reject a certificate request.
     */
    public function rejectRequest(CertificateRequest $request, User $rejector): CertificateRequest
    {
        $request->update([
            'status' => CertificateRequest::STATUS_REJECTED,
            'approved_by' => $rejector->id,
            'approved_at' => now(),
        ]);

        return $request->fresh();
    }

    /**
     * Get count of pending approvals for sidebar badge.
     */
    public function getApprovalCount(User $user): int
    {
        if (!$user->isManager() && !$user->isAdmin()) {
            return 0;
        }

        $query = CertificateRequest::where('status', CertificateRequest::STATUS_PENDING);

        // If manager, filter by managed sub-departments
        if ($user->isManager() && !$user->isAdmin()) {
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();

            if (!empty($managedSubDepartmentIds)) {
                $query->whereHas('user', function ($q) use ($managedSubDepartmentIds) {
                    $q->whereIn('sub_department_id', $managedSubDepartmentIds);
                });
            } else {
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('department_id', $user->department_id);
                });
            }
        }

        return $query->count();
    }

    /**
     * Generate certificate PDF.
     */
    public function generateCertificatePdf(CertificateRequest $request)
    {
        $request->load(['user.department', 'user.subDepartment', 'issuer']);

        $data = [
            'request' => $request,
            'user' => $request->user,
            'issueDate' => $request->issued_at ?? now(),
            'issuer' => config('services_module.issuer'),
            'company' => config('services_module.company'),
        ];

        $pdf = Pdf::loadView('pdf.certificate', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Check if user can approve a request.
     */
    public function canApprove(User $user, CertificateRequest $request): bool
    {
        if (!$request->isPending()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isManager()) {
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();

            if (!empty($managedSubDepartmentIds)) {
                return in_array($request->user->sub_department_id, $managedSubDepartmentIds);
            }

            return $request->user->department_id === $user->department_id;
        }

        return false;
    }

    /**
     * Check if user can view a certificate request (for review page, even after issued).
     */
    public function canView(User $user, CertificateRequest $request): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isManager()) {
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();

            if (!empty($managedSubDepartmentIds)) {
                return in_array($request->user->sub_department_id, $managedSubDepartmentIds);
            }

            return $request->user->department_id === $user->department_id;
        }

        return false;
    }

    /**
     * Get employee info for certificate display.
     */
    public function getEmployeeInfo(User $user): array
    {
        $user->load(['department', 'subDepartment']);

        return [
            'id' => $user->id,
            'employee_id' => $user->employee_id,
            'name' => $user->name,
            'email' => $user->email,
            'designation' => $user->designation,
            'department' => $user->department?->name,
            'sub_department' => $user->subDepartment?->name,
            'fathers_name' => $user->fathers_name,
            'mothers_name' => $user->mothers_name,
            'nid_number' => $user->nid_number,
            'joining_date' => $user->joining_date?->format('F d, Y'),
            'nationality' => $user->nationality ?? 'Bangladeshi',
        ];
    }
}

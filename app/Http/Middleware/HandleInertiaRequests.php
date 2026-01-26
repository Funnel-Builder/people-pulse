<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $quoteImages = glob(public_path('quoteimages/*.png'));
        $randomImage = '/quoteimages/' . basename($quoteImages[array_rand($quoteImages)]);

        $user = $request->user();
        $pendingCoverRequests = 0;
        $pendingLeaveApprovals = 0;
        $pendingCertificateApprovals = 0;

        if ($user) {
            $user->load(['department:id,name', 'subDepartment:id,name']);

            // Get pending approval counts for menu badges
            $leaveService = app(\App\Services\LeaveService::class);
            $pendingCoverRequests = $leaveService->getCoverRequestCount($user);
            $pendingLeaveApprovals = $leaveService->getLeaveApprovalCount($user);

            // Get pending certificate approval count
            $certificateService = app(\App\Services\CertificateService::class);
            $pendingCertificateApprovals = $certificateService->getApprovalCount($user);
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author), 'image' => $randomImage],
            'auth' => [
                'user' => $user,
                'pendingCoverRequests' => $pendingCoverRequests,
                'pendingLeaveApprovals' => $pendingLeaveApprovals,
                'pendingCertificateApprovals' => $pendingCertificateApprovals,
            ],
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],
        ];
    }
}

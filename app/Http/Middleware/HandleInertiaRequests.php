<?php

namespace App\Http\Middleware;

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
        try {
            $setting = \Illuminate\Support\Facades\DB::table('app_settings')->first();
            $branchName = $setting ? $setting->branch_name : ((strtolower(explode('.', $request->getHost())[0] ?? 'jakarta') === 'bekasi') ? 'LMS 11 Maret Bekasi' : 'LMS 11 Maret Jakarta');
        } catch (\Exception $e) {
            $branchName = (strtolower(explode('.', $request->getHost())[0] ?? 'jakarta') === 'bekasi') ? 'LMS 11 Maret Bekasi' : 'LMS 11 Maret Jakarta';
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                    'id_guru' => $request->user()->id_guru,
                    'id_siswa' => $request->user()->id_siswa,
                    'is_walikelas' => $request->user()->role === 'TEACHER' && \App\Models\Clas::where('id_guru_wali', $request->user()->id_guru)->exists(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'app' => [
                'branch' => [
                    'code' => (strtolower(explode('.', $request->getHost())[0] ?? 'jakarta') === 'bekasi') ? 'BKS' : 'JKT',
                    'name' => $branchName,
                ],
                'is_local_env' => app()->environment(['local', 'development']),
                'current_time' => now()->format('Y-m-d H:i:s'),
                'is_mock_time' => \Illuminate\Support\Facades\Cache::has('time_offset'),
                'today_schedules' => \Illuminate\Support\Facades\Cache::remember('jp_schedules_today_' . now()->dayOfWeek, 60 * 60, function () {
                    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    return \App\Models\JpSchedule::where('hari', $days[now()->dayOfWeek])->get();
                }),
            ],
        ];
    }
}

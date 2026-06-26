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
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'app' => [
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

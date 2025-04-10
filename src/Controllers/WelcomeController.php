<?php

namespace Brucelwayne\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mallria\Core\Facades\InertiaAdminFacade;
use Mallria\Core\Http\Responses\SuccessJsonResponse;
use Mallria\Passport\Models\UserStateModel;

class WelcomeController extends Controller
{
    function index(Request $request)
    {

        // 获取今天的开始时间和昨天的开始与结束时间
        $todayStart = Carbon::today();
        $yesterdayStart = Carbon::yesterday();
        $yesterdayEnd = $todayStart;

        // 缓存今天活跃的 user state 数量，一小时更新一次
        $activeUserStatesCount = Cache::tags([UserStateModel::TABLE])->remember('active_user_state_count', 3600, function () use ($todayStart) {
            return UserStateModel::where('updated_at', '>=', $todayStart)->count();
        });

        // 缓存今天活跃的唯一 device_id 数量，一小时更新一次
        $uniqueDeviceCount = Cache::tags([UserStateModel::TABLE])->remember('active_device_count', 3600, function () use ($todayStart) {
            return UserStateModel::where('updated_at', '>=', $todayStart)
                ->distinct('device_id')
                ->count('device_id');
        });

        // 将数据传递给前端
        return InertiaAdminFacade::render('Admin/Dashboard/Index', [
            'active_user_state_count' => $activeUserStatesCount,
            'active_device_count' => $uniqueDeviceCount,
        ]);
    }

    function clearCache(Request $request)
    {
        $key = $request->get('cache-key');
        $tag = $request->get('cache-tag');
        $clearOpcache = $request->get('opcache'); // 添加参数来触发 OPcache 清除

        // 处理 OPcache 清除
        if ($clearOpcache) {
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
        }

        // 处理普通缓存清除
        if ($tag) {
            Cache::tags($tag)->flush();
        }

        if ($key) {
            Cache::forget($key);
        }

        return new SuccessJsonResponse();
    }
}
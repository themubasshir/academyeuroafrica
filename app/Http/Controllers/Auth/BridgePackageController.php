<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CourseVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BridgePackageController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $path = '/'.ltrim($request->string('path')->toString(), '/');
        abort_unless($path !== '/', 404);

        $mapping = DB::table('joomla_page_mappings')->where('path', $path)->first();
        abort_unless($mapping !== null, 404);

        $requestedVariantId = $request->integer('variant');
        $variant = null;
        if ($mapping->course_variant_id !== null) {
            abort_unless(
                $requestedVariantId === 0 || $requestedVariantId === (int) $mapping->course_variant_id,
                404
            );
            $variant = CourseVariant::with('course')->find((int) $mapping->course_variant_id);
        } elseif ($mapping->programme_id !== null && $requestedVariantId > 0) {
            $variant = CourseVariant::with('course')
                ->whereKey($requestedVariantId)
                ->whereHas('course', fn ($query) => $query->where('programme_id', $mapping->programme_id))
                ->first();
        }

        abort_unless($variant !== null, 404);
        abort_unless($variant->isPurchasable(), 404);

        $intent = [
            'variant_id' => $variant->id,
            'mapping_id' => $mapping->id,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ];
        $request->session()->put('bridge_intent', $intent);
        $request->session()->put('url.intended', route('checkout.show', $variant));

        if ($request->user()?->isActive()) {
            return redirect()->route('checkout.show', $variant);
        }

        return redirect()->route('bridge.start');
    }
}

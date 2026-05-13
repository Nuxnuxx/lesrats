<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OnboardingController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Deja onboarded → aller directement au dashboard.
        if (! $user->needsOnboarding()) {
            return redirect()->route('dashboard');
        }

        // Shortcut : si user a fait shop + extension ET a deja importe un produit,
        // on le marque onboarded et on l'envoie directement editer ce produit.
        if ($user->hasOwnedShop() && $user->hasExtensionToken() && $user->hasImportedProduct()) {
            $product = Product::whereIn('shop_id', $user->ownedShops()->pluck('shops.id'))
                ->latest('id')
                ->first();

            $user->forceFill(['onboarded_at' => now()])->save();

            return redirect()->route('products.edit', $product)
                ->with('success', 'Bienvenue sur LesRats ! Voici votre premier produit.');
        }

        return view('onboarding.show', [
            'user' => $user,
            'hasShop' => $user->hasOwnedShop(),
            'hasExtension' => $user->hasExtensionToken(),
            'hasProduct' => $user->hasImportedProduct(),
            'ownedShop' => $user->ownedShops()->first(),
            'tokens' => $user->tokens()->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->canCompleteOnboarding(), 422, 'Onboarding incomplet.');

        $user->forceFill(['onboarded_at' => now()])->save();

        return redirect()->route('dashboard')->with('success', 'Bienvenue sur LesRats !');
    }

    /**
     * Build and stream a ZIP of the browser-extension folder.
     * Small enough (~340KB, 23 files) to zip on every request without a cache layer.
     */
    public function downloadExtension(): BinaryFileResponse
    {
        $sourceDir = base_path('browser-extension');

        abort_unless(is_dir($sourceDir), 404, 'Extension source introuvable.');

        $manifest = json_decode(file_get_contents($sourceDir.'/manifest.json'), true) ?? [];
        $version = $manifest['version'] ?? '0.0.0';
        $filename = "lesrats-extension-v{$version}.zip";

        $tmpPath = tempnam(sys_get_temp_dir(), 'lesrats-ext-').'.zip';

        $zip = new \ZipArchive;
        if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Impossible de creer l\'archive.');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            $localPath = substr($file->getPathname(), strlen($sourceDir) + 1);
            $zip->addFile($file->getPathname(), $localPath);
        }

        $zip->close();

        return response()
            ->download($tmpPath, $filename, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }
}

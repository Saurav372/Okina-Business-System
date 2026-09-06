<?php

namespace App\Http\Controllers\Api;

use App\Contracts\CustomizationOptionContract;
use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use App\Models\Product;
use App\Models\StoredFile;
use App\Services\FileUploadService;
use App\Services\ProtectedMockupService;
use App\Support\Products\CustomizationSnapshotBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductCustomizationController extends Controller
{
    public function __construct(
        private readonly CustomizationOptionContract $rules,
        private readonly CustomizationSnapshotBuilder $snapshots,
    ) {}

    public function show(Product $product): JsonResponse
    {
        if (! $product->isPubliclyVisible()) {
            abort(404);
        }

        return response()->json([
            'data' => $this->rules->product($product->slug),
            'guidance' => $this->rules->guidance(),
        ]);
    }

    public function store(Request $request, Product $product, FileUploadService $files): JsonResponse
    {
        if (! $product->isPubliclyVisible()) {
            abort(404);
        }

        $actor = Auth::guard('customer')->user();
        abort_unless($actor instanceof CustomerAccount, 403);

        $request->validate([
            'design_file' => ['required', 'file'],
            'sku_code' => ['required', 'string', 'max:80'],
            'selected_options' => ['nullable', 'array'],
            'print_position' => ['required', 'string', 'max:50'],
            'print_method' => ['required', 'string', 'max:50'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'placement' => ['nullable', 'array'],
            'placement.x' => ['nullable', 'numeric'],
            'placement.y' => ['nullable', 'numeric'],
            'placement.scale' => ['nullable', 'numeric'],
            'placement.rotation' => ['nullable', 'numeric'],
        ]);

        $selection = $this->selectionPayload($request);
        $validation = $this->rules->validateSelection($product->slug, $selection);

        if (! $validation['valid']) {
            throw ValidationException::withMessages([
                'selection' => $validation['errors'],
            ]);
        }

        $file = $files->store($request->file('design_file'), $actor, [
            'file_kind' => StoredFile::KIND_ORIGINAL_UPLOAD,
            'visibility' => StoredFile::VISIBILITY_PRIVATE,
            'customer_id' => $actor->customer_id,
        ]);

        $customizationSnapshot = $this->snapshots->build(
            product: $product,
            selection: $selection,
            validation: $validation,
            file: $file,
            placement: $request->input('placement', []),
            selectedOptions: $request->input('selected_options', []),
            customerNote: $request->string('customer_note')->trim()->toString(),
        );

        $file->forceFill([
            'metadata' => array_merge($file->metadata ?? [], [
                'customization' => $customizationSnapshot,
            ]),
        ])->save();

        $file = $file->refresh();

        return response()->json([
            'data' => [
                'product' => [
                    'slug' => $product->slug,
                    'name' => $product->name,
                ],
                'selection' => [
                    'sku_code' => $validation['matched_sku']['sku_code'] ?? $selection['sku_code'],
                    'variant_key' => $validation['resolved_variant_key'],
                    'selected_options_snapshot' => $customizationSnapshot['selected_options_snapshot'] ?? [],
                    'print_method' => $selection['print_method'],
                    'print_position' => $selection['print_position'],
                    'placement' => $customizationSnapshot['placement'],
                    'customer_note' => $request->string('customer_note')->trim()->toString() ?: null,
                ],
                'file' => $this->filePayload($file, $files),
                'customization_snapshot' => $customizationSnapshot,
            ],
            'guidance' => $this->rules->guidance(),
        ]);
    }

    public function preview(Request $request, Product $product, string $preview_file): Response
    {
        if (! $product->isPubliclyVisible()) {
            abort(404);
        }

        $request->validate([
            'print_position' => ['required', 'string'],
            'print_method' => ['required', 'string'],
            'placement' => ['nullable', 'array'],
            'placement.x' => ['nullable', 'numeric'],
            'placement.y' => ['nullable', 'numeric'],
            'placement.scale' => ['nullable', 'numeric'],
            'placement.rotation' => ['nullable', 'numeric'],
        ]);

        $file = StoredFile::query()->where('public_id', $preview_file)->firstOrFail();
        $placement = $this->snapshots->normalizePlacement($request->input('placement', []));
        $design = $this->previewDesignData($file);
        $svg = $this->renderMockupSvg(
            productName: $product->name,
            productSlug: $product->slug,
            printPosition: $request->string('print_position')->trim()->toString(),
            printMethod: $request->string('print_method')->trim()->toString(),
            placement: $placement,
            design: $design,
            fileName: $file->original_filename,
        );

        return response($svg, 200)
            ->header('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->header('Cache-Control', 'private, max-age=60');
    }

    public function previewLink(Request $request, Product $product, string $preview_file): RedirectResponse
    {
        if (! $product->isPubliclyVisible()) {
            abort(404);
        }

        $request->validate([
            'print_position' => ['required', 'string'],
            'print_method' => ['required', 'string'],
            'placement' => ['nullable', 'array'],
            'placement.x' => ['nullable', 'numeric'],
            'placement.y' => ['nullable', 'numeric'],
            'placement.scale' => ['nullable', 'numeric'],
            'placement.rotation' => ['nullable', 'numeric'],
        ]);

        $actor = Auth::guard('customer')->user();
        abort_unless($actor instanceof CustomerAccount, 403);

        $file = StoredFile::query()->where('public_id', $preview_file)->firstOrFail();
        Gate::forUser($actor)->authorize('preview', $file);

        $selection = [
            'print_position' => $request->string('print_position')->trim()->toString(),
            'print_method' => $request->string('print_method')->trim()->toString(),
        ];
        $placement = $this->snapshots->normalizePlacement($request->input('placement', []));

        return redirect()->away(URL::temporarySignedRoute(
            'catalog.products.mockup-preview',
            now()->addMinutes(15),
            $this->mockupPreviewRouteParameters($product, $file, $selection, $placement)
        ));
    }

    public function protectedMockup(
        Request $request,
        Product $product,
        StoredFile $preview_file,
        FileUploadService $files,
        ProtectedMockupService $mockups,
    ): JsonResponse {
        if (! $product->isPubliclyVisible()) {
            abort(404);
        }

        $actor = Auth::guard('customer')->user();
        abort_unless($actor instanceof CustomerAccount, 403);
        Gate::forUser($actor)->authorize('preview', $preview_file);

        abort_unless(
            $preview_file->file_kind === StoredFile::KIND_ORIGINAL_UPLOAD
                && $preview_file->status === StoredFile::STATUS_ACTIVE,
            422,
            'Only an active original artwork upload can be used.'
        );

        $productOptions = $this->rules->product($product->slug);
        $allowedPositions = collect($productOptions['print_positions'] ?? [])
            ->pluck('code')
            ->filter()
            ->values()
            ->all();

        $validated = $request->validate([
            'color_code' => ['required', 'string', 'max:50'],
            'print_position' => ['required', 'string', Rule::in($allowedPositions)],
            'placement' => ['nullable', 'array'],
            'placement.x' => ['nullable', 'numeric', 'between:0,100'],
            'placement.y' => ['nullable', 'numeric', 'between:0,100'],
            'placement.scale' => ['nullable', 'numeric', 'between:0.6,1.2'],
        ]);

        $placement = $this->snapshots->normalizePlacement($validated['placement'] ?? []);
        $preview_file = $files->ensurePreview($preview_file);
        $mockup = $mockups->create(
            product: $product,
            source: $preview_file,
            actor: $actor,
            colorCode: $validated['color_code'],
            printPosition: $validated['print_position'],
            placement: $placement,
        );

        return response()->json([
            'data' => [
                'file' => $this->snapshots->fileReference($mockup, 'protected_mockup'),
                'preview_url' => $files->temporaryPreviewUrl($mockup, 60),
                'download_url' => $files->temporaryDownloadUrl($mockup, 60),
                'watermark' => [
                    'applied' => true,
                    'version' => ProtectedMockupService::WATERMARK_VERSION,
                    'label' => 'Protected preview — not for production',
                ],
            ],
        ], 201);
    }

    private function selectionPayload(Request $request): array
    {
        return [
            'sku_code' => $request->string('sku_code')->trim()->toString(),
            'selected_options' => $request->input('selected_options', []),
            'print_position' => $request->string('print_position')->trim()->toString(),
            'print_method' => $request->string('print_method')->trim()->toString(),
        ];
    }

    private function filePayload(StoredFile $file, FileUploadService $files): array
    {
        return [
            'public_id' => $file->public_id,
            'file_kind' => $file->file_kind,
            'visibility' => $file->visibility,
            'status' => $file->status,
            'original_filename' => $file->original_filename,
            'mime_type' => $file->mime_type,
            'size_bytes' => $file->size_bytes,
            'has_preview' => $file->hasPreview(),
            'preview_url' => $file->hasPreview() ? $files->temporaryPreviewUrl($file) : null,
            'download_url' => $files->temporaryDownloadUrl($file),
            'preview' => $this->previewPayload($file),
        ];
    }

    private function previewPayload(StoredFile $file): ?array
    {
        $preview = $file->previewMetadata();

        if ($preview === null) {
            return null;
        }

        return [
            'mime_type' => $preview['mime_type'] ?? null,
            'size_bytes' => $preview['size_bytes'] ?? null,
            'width' => $preview['width'] ?? null,
            'height' => $preview['height'] ?? null,
        ];
    }

    private function mockupPreviewRouteParameters(Product $product, StoredFile $file, array $selection, mixed $placement): array
    {
        return [
            'product' => $product->slug,
            'preview_file' => $file->public_id,
            'print_position' => $selection['print_position'],
            'print_method' => $selection['print_method'],
            'placement' => $this->snapshots->normalizePlacement($placement),
        ];
    }

    private function previewDesignData(StoredFile $file): ?array
    {
        if (! $file->hasPreview()) {
            return null;
        }

        $disk = Storage::disk($file->previewStorageDisk() ?? $file->storage_disk);
        $path = $file->previewPath();

        if ($path === null || ! $disk->exists($path)) {
            return null;
        }

        $mimeType = $file->previewMimeType() ?? $file->mime_type;

        return [
            'mime_type' => $mimeType,
            'data_uri' => 'data:'.$mimeType.';base64,'.base64_encode($disk->get($path)),
        ];
    }

    private function renderMockupSvg(
        string $productName,
        string $productSlug,
        string $printPosition,
        string $printMethod,
        array $placement,
        ?array $design,
        ?string $fileName,
    ): string {
        $canvasWidth = 1200;
        $canvasHeight = 900;
        $printAreaX = 420;
        $printAreaY = 200;
        $printAreaWidth = 360;
        $printAreaHeight = 420;
        $placementX = $placement['x'] ?? 50.0;
        $placementY = $placement['y'] ?? 50.0;
        $placementScale = $placement['scale'] ?? 1.0;
        $placementRotation = $placement['rotation'] ?? 0.0;
        $designWidth = max(140, (int) round($printAreaWidth * 0.78 * $placementScale));
        $designHeight = max(140, (int) round($printAreaHeight * 0.78 * $placementScale));
        $designX = (int) round($printAreaX + ($placementX / 100) * $printAreaWidth - ($designWidth / 2));
        $designY = (int) round($printAreaY + ($placementY / 100) * $printAreaHeight - ($designHeight / 2));
        $rotation = number_format((float) $placementRotation, 2, '.', '');

        $safeProductName = htmlspecialchars($productName, ENT_QUOTES, 'UTF-8');
        $safeProductSlug = htmlspecialchars($productSlug, ENT_QUOTES, 'UTF-8');
        $safePrintPosition = htmlspecialchars($printPosition, ENT_QUOTES, 'UTF-8');
        $safePrintMethod = htmlspecialchars($printMethod, ENT_QUOTES, 'UTF-8');
        $safeFileName = htmlspecialchars((string) ($fileName ?? 'uploaded design'), ENT_QUOTES, 'UTF-8');
        $safeMetaLine = htmlspecialchars($safeProductSlug.' - '.$safePrintPosition.' - '.$safePrintMethod, ENT_QUOTES, 'UTF-8');
        $safePlacementLine = htmlspecialchars('x '.$placementX.' / y '.$placementY.' / scale '.$placementScale.' / rotation '.$placementRotation, ENT_QUOTES, 'UTF-8');

        $designMarkup = $design === null
            ? sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" rx="24" fill="#efe7da" stroke="#c8bca8" stroke-dasharray="8 8" />',
                $designX,
                $designY,
                $designWidth,
                $designHeight,
            ).sprintf(
                '<text x="%d" y="%d" text-anchor="middle" font-size="24" fill="#5f6b7a">Preview unavailable</text>',
                (int) ($canvasWidth / 2),
                (int) ($canvasHeight / 2),
            ).sprintf(
                '<text x="%d" y="%d" text-anchor="middle" font-size="16" fill="#7b8794">%s</text>',
                (int) ($canvasWidth / 2),
                (int) (($canvasHeight / 2) + 28),
                $safeFileName,
            )
            : sprintf(
                '<image href="%s" x="%d" y="%d" width="%d" height="%d" preserveAspectRatio="xMidYMid meet" transform="rotate(%s %d %d)" />',
                $design['data_uri'],
                $designX,
                $designY,
                $designWidth,
                $designHeight,
                $rotation,
                (int) round($designX + ($designWidth / 2)),
                (int) round($designY + ($designHeight / 2)),
            );

        return implode("\n", [
            '<?xml version="1.0" encoding="UTF-8"?>',
            sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-labelledby="title desc">',
                $canvasWidth,
                $canvasHeight,
                $canvasWidth,
                $canvasHeight,
            ),
            sprintf('<title id="title">%s mockup preview</title>', $safeProductName),
            sprintf('<desc id="desc">%s placement preview for %s</desc>', $safePrintPosition.' '.$safePrintMethod, $safeProductName),
            sprintf('<rect width="%d" height="%d" fill="#f6f4ef" />', $canvasWidth, $canvasHeight),
            '<rect x="60" y="60" width="1080" height="780" rx="36" fill="#ffffff" stroke="#d8d2c6" stroke-width="2" />',
            sprintf('<text x="100" y="125" font-size="42" font-weight="700" fill="#1f2937">%s</text>', $safeProductName),
            sprintf('<text x="100" y="164" font-size="20" fill="#5f6b7a">%s</text>', $safeMetaLine),
            sprintf('<rect x="%d" y="%d" width="%d" height="%d" rx="32" fill="#f8f6f1" stroke="#c8bca8" stroke-width="2" stroke-dasharray="12 10" />', $printAreaX, $printAreaY, $printAreaWidth, $printAreaHeight),
            sprintf('<text x="%d" y="%d" font-size="18" fill="#6b7280">Print area</text>', $printAreaX, $printAreaY - 18),
            $designMarkup,
            sprintf('<text x="100" y="785" font-size="18" fill="#7b8794">Uploaded file: %s</text>', $safeFileName),
            sprintf('<text x="100" y="815" font-size="18" fill="#7b8794">Placement: %s</text>', $safePlacementLine),
            '</svg>',
        ]);
    }
}

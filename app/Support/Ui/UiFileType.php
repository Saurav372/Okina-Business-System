<?php

namespace App\Support\Ui;

final class UiFileType
{
    // Exact MIME types checked first
    private const EXACT = [
        'application/pdf' => [
            'previewType' => 'pdf',
            'icon' => 'pdf',
            'label' => 'PDF',
            'accent' => 'danger',
            'iconClass' => 'text-red-600 dark:text-red-400',
            'bgClass' => 'bg-red-100 dark:bg-red-950/30',
        ],
        'application/zip' => [
            'previewType' => 'download',
            'icon' => 'archive',
            'label' => 'ZIP',
            'accent' => 'warning',
            'iconClass' => 'text-amber-600 dark:text-amber-400',
            'bgClass' => 'bg-amber-100 dark:bg-amber-950/30',
        ],
        'application/x-zip-compressed' => [
            'previewType' => 'download',
            'icon' => 'archive',
            'label' => 'ZIP',
            'accent' => 'warning',
            'iconClass' => 'text-amber-600 dark:text-amber-400',
            'bgClass' => 'bg-amber-100 dark:bg-amber-950/30',
        ],
        'application/msword' => [
            'previewType' => 'download',
            'icon' => 'doc',
            'label' => 'Word',
            'accent' => 'info',
            'iconClass' => 'text-blue-600 dark:text-blue-400',
            'bgClass' => 'bg-blue-100 dark:bg-blue-950/30',
        ],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => [
            'previewType' => 'download',
            'icon' => 'doc',
            'label' => 'Word',
            'accent' => 'info',
            'iconClass' => 'text-blue-600 dark:text-blue-400',
            'bgClass' => 'bg-blue-100 dark:bg-blue-950/30',
        ],
        'application/vnd.ms-excel' => [
            'previewType' => 'download',
            'icon' => 'sheet',
            'label' => 'Excel',
            'accent' => 'success',
            'iconClass' => 'text-emerald-600 dark:text-emerald-400',
            'bgClass' => 'bg-emerald-100 dark:bg-emerald-950/30',
        ],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => [
            'previewType' => 'download',
            'icon' => 'sheet',
            'label' => 'Excel',
            'accent' => 'success',
            'iconClass' => 'text-emerald-600 dark:text-emerald-400',
            'bgClass' => 'bg-emerald-100 dark:bg-emerald-950/30',
        ],
    ];

    // Ordered longest-first at definition time — no runtime sort needed
    private const PREFIX = [
        'image/' => [
            'previewType' => 'image',
            'icon' => 'image',
            'label' => 'Image',
            'accent' => 'info',
            'iconClass' => 'text-blue-600 dark:text-blue-400',
            'bgClass' => 'bg-blue-100 dark:bg-blue-950/30',
        ],
        'video/' => [
            'previewType' => 'video',
            'icon' => 'video',
            'label' => 'Video',
            'accent' => 'primary',
            'iconClass' => 'text-purple-600 dark:text-purple-400',
            'bgClass' => 'bg-purple-100 dark:bg-purple-950/30',
        ],
        'audio/' => [
            'previewType' => 'audio',
            'icon' => 'audio',
            'label' => 'Audio',
            'accent' => 'primary',
            'iconClass' => 'text-pink-600 dark:text-pink-400',
            'bgClass' => 'bg-pink-100 dark:bg-pink-950/30',
        ],
        'text/' => [
            'previewType' => 'download',
            'icon' => 'text',
            'label' => 'Text',
            'accent' => 'info',
            'iconClass' => 'text-teal-600 dark:text-teal-400',
            'bgClass' => 'bg-teal-100 dark:bg-teal-950/30',
        ],
        'application/' => [
            'previewType' => 'download',
            'icon' => 'file',
            'label' => 'File',
            'accent' => 'neutral',
            'iconClass' => 'text-neutral-500 dark:text-neutral-400',
            'bgClass' => 'bg-neutral-100 dark:bg-neutral-800',
        ],
    ];

    private const FALLBACK = [
        'previewType' => 'download',
        'icon' => 'file',
        'label' => 'File',
        'accent' => 'neutral',
        'iconClass' => 'text-neutral-500 dark:text-neutral-400',
        'bgClass' => 'bg-neutral-100 dark:bg-neutral-800',
    ];

    private const ALLOWED_PREVIEW_TYPES = ['image', 'video', 'audio', 'pdf', 'download'];

    /** @return array{previewType:string, icon:string, label:string, accent:string, iconClass:string, bgClass:string} */
    public static function resolve(?string $mime, ?string $forcePreviewType = null): array
    {
        $result = null;

        if ($mime) {
            // Stage 1: exact match
            if (isset(self::EXACT[$mime])) {
                $result = self::EXACT[$mime];
            } else {
                // Stage 2: prefix match
                foreach (self::PREFIX as $prefix => $config) {
                    if (str_starts_with($mime, $prefix)) {
                        $result = $config;
                        break;
                    }
                }
            }
        }

        $result ??= self::FALLBACK;

        // Override previewType
        if ($forcePreviewType !== null && in_array($forcePreviewType, self::ALLOWED_PREVIEW_TYPES, true)) {
            $result['previewType'] = $forcePreviewType;
        }

        return $result;
    }
}

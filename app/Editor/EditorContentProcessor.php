<?php

namespace App\Editor;

use App\Services\Article\SeoLinkPolicyProcessorNew;

final class EditorContentProcessor
{
    private const DEFAULT_OPTIONS = [
        'do_follow'    => false,
        'target_blank' => true,
        'action'       => EditorService::SAVE_ACTION,
    ];


    /**
     * @param EditorService $editorService
     * @param SeoLinkPolicyProcessorNew $seoLinkPolicyProcessor
     */
    public function __construct(
        private readonly EditorService          $editorService,
        private readonly SeoLinkPolicyProcessorNew $seoLinkPolicyProcessor,
    )
    {
    }

    /**
     * @param string $content
     * @param array $options
     * @return string
     */
    public function process(
        string $content,
        array  $options = []
    ): string
    {
        $options = array_merge(
            self::DEFAULT_OPTIONS,
            $options
        );

        $editor = $this->editorService::make($content, $options['action']);

        $content = $editor->toArray();

        $content = $this->seoLinkPolicyProcessor->process(
            $content,
            $options
        );

        return json_encode(
            $content,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );
    }
}
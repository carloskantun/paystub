<?php
namespace App\Controllers;

class TemplateController
{
    public function list()
    {
        $templates = templates_config();
        $public = [];
        foreach ($templates as $key => $tpl) {
            if (!empty($tpl['hidden'])) {
                continue;
            }
            $public[] = [
                'key' => $tpl['key'] ?? $key,
                'name' => $tpl['name'] ?? $key,
                'description' => $tpl['description'] ?? '',
                'thumbnail_svg' => $tpl['thumbnail_svg'] ?? '',
                'pdf_view' => $tpl['pdf_view'] ?? null,
                'tokens' => $tpl['tokens'] ?? [],
                'layout' => $tpl['layout'] ?? [],
            ];
        }

        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=300');
        echo json_encode(['templates' => $public]);
    }
}

<?php
/**
 * Preview frame: renders the selected PDF layout HTML with a watermark overlay.
 * Expects $payload with keys per contract, including:
 *  - template_key
 *  - periods (array of periods for N stubs)
 *  - branding.watermark (string)
 */
$templates = templates_config();
$tplKey = $payload['template_key'] ?? 'horizontal_blue';
if (!isset($templates[$tplKey])) { $tplKey = 'horizontal_blue'; }
$viewFile = __DIR__ . '/../' . ($templates[$tplKey]['pdf_view'] ?? 'pdf-layout-classic-black.php');
$tokens = $templates[$tplKey]['tokens'] ?? [];
$layout = $templates[$tplKey]['layout'] ?? [];

// Render N stubs, each as a page; include watermark overlay visually (HTML-only preview)
?>
<div class="preview-canvas-wrapper" style="position:relative;">
  <!-- Watermark scoped (absolute) so it does not cover carousel / rest of UI -->
  <div class="watermark-overlay" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;z-index:2;opacity:.9;">
      <div style="transform:rotate(-25deg);font-size:64px;font-weight:800;letter-spacing:8px;color:rgba(0,0,0,.06);text-align:center;">
        <?= htmlspecialchars($payload['branding']['watermark'] ?? 'PREVIEW ONLY') ?>
      </div>
  </div>
    <div class="preview-canvas" style="position:relative;z-index:1;min-width:100%;">
      <?php foreach (($payload['periods'] ?? [null]) as $i => $period):
        $stubPayload = $payload;
        $stubPayload['stub_index'] = $i;
        $stubPayload['period'] = $period; ?>
  <div class="stub-page" data-stub-index="<?= (int)$i ?>" style="background:#fff;color:#0f172a;padding:12px;border:1px solid #e5e7eb;border-radius:8px;margin:0 0 16px;">
          <?php $tokens = $tokens; $layout = $layout; $payload = $stubPayload; include $viewFile; ?>
        </div>
        <?php if ($i < count(($payload['periods'] ?? [])) - 1): ?>
          <div class="page-break"></div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>

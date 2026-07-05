<!-- Theme Customizer (offcanvas) — reusable, applies instantly via CSS variables -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="themePanel" aria-labelledby="themePanelLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="themePanelLabel"><i class="bi bi-palette me-2"></i>Theme Customizer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <p class="text-secondary small">Changes preview instantly. Click <strong>Save</strong> to keep them.</p>

        <!-- Appearance mode -->
        <div class="mb-4">
            <label class="form-label fw-semibold">Appearance</label>
            <div class="btn-group w-100" role="group">
                <button type="button" class="btn btn-outline-secondary" data-set-mode="light"><i class="bi bi-sun me-1"></i>Light</button>
                <button type="button" class="btn btn-outline-secondary" data-set-mode="dark"><i class="bi bi-moon-stars me-1"></i>Dark</button>
            </div>
        </div>

        <hr>

        <!-- Quick primary presets -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Preset colors</label>
            <div class="erp-preset-grid" id="themePresets"></div>
        </div>

        <div class="mb-3 d-flex justify-content-between align-items-center">
            <label for="themePrimary" class="form-label mb-0 fw-semibold">Primary color</label>
            <input type="color" class="form-control form-control-color" id="themePrimary" value="#0d6efd">
        </div>

        <div class="mb-3 d-flex justify-content-between align-items-center">
            <label for="themeSecondary" class="form-label mb-0 fw-semibold">Secondary color</label>
            <input type="color" class="form-control form-control-color" id="themeSecondary" value="#6c757d">
        </div>

        <div class="mb-3">
            <div class="form-check form-switch mb-1">
                <input class="form-check-input" type="checkbox" id="themeFontEnable">
                <label class="form-check-label fw-semibold" for="themeFontEnable">Custom font color</label>
            </div>
            <input type="color" class="form-control form-control-color" id="themeFont" value="#212529">
        </div>

        <div class="mb-3">
            <div class="form-check form-switch mb-1">
                <input class="form-check-input" type="checkbox" id="themeBgEnable">
                <label class="form-check-label fw-semibold" for="themeBgEnable">Custom background color</label>
            </div>
            <input type="color" class="form-control form-control-color" id="themeBg" value="#f4f6f9">
        </div>

        <div class="d-grid gap-2 mt-4">
            <button type="button" class="btn btn-primary" id="themeSave"><i class="bi bi-save me-1"></i> Save Theme</button>
            <button type="button" class="btn btn-outline-danger" id="themeReset"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Default</button>
        </div>
    </div>
</div>

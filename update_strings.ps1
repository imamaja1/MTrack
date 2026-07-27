# 1. Update #[Layout('layouts.app')] to #[Layout('components.layouts.app')]
$livewireFiles = Get-ChildItem -Path app\Livewire -Recurse -Filter *.php
foreach ($file in $livewireFiles) {
    $content = Get-Content $file.FullName -Raw
    if ($content -match "#\[Layout\('layouts\.app'\)\]") {
        $content = $content -replace "#\[Layout\('layouts\.app'\)\]", "#[Layout('components.layouts.app')]"
        Set-Content $file.FullName $content
    }
}

# 2. Update <x-layouts:: to <x-layouts. in all views
$viewFiles = Get-ChildItem -Path resources\views -Recurse -Filter *.blade.php
foreach ($file in $viewFiles) {
    $content = Get-Content $file.FullName -Raw
    if ($content -match "x-layouts::") {
        $content = $content -replace "x-layouts::", "x-layouts."
        Set-Content $file.FullName $content
    }
    
    # 3. Update @include('partials. to @include('components.partials.
    if ($content -match "@include\('partials\.") {
        $content = $content -replace "@include\('partials\.", "@include('components.partials."
        Set-Content $file.FullName $content
    }
}

# 4. Update pages::auth to livewire.auth in FortifyServiceProvider
$fortifyFile = "app\Providers\FortifyServiceProvider.php"
if (Test-Path $fortifyFile) {
    $content = Get-Content $fortifyFile -Raw
    $content = $content -replace "pages::auth\.", "livewire.auth."
    Set-Content $fortifyFile $content
}

# 5. Update pages::settings to livewire.settings in routes/settings.php
$settingsFile = "routes\settings.php"
if (Test-Path $settingsFile) {
    $content = Get-Content $settingsFile -Raw
    $content = $content -replace "pages::settings\.", "livewire.settings."
    Set-Content $settingsFile $content
}

echo "All string replacements complete."

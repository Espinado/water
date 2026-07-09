param(
    [string]$HostAddress = "127.0.0.1",
    [int]$Port = 8188,
    [switch]$OpenBrowser
)

$ErrorActionPreference = "Stop"
Set-StrictMode -Version Latest

$ToolRoot = $PSScriptRoot
$ComfyRoot = Join-Path $ToolRoot "ComfyUI"
$VenvPython = Join-Path $ToolRoot ".venv\Scripts\python.exe"
$CheckpointRoot = Join-Path $ComfyRoot "models\checkpoints"

if (-not (Test-Path $ComfyRoot)) {
    throw "ComfyUI is not installed. Run: npm run image:install"
}

if (-not (Test-Path $VenvPython)) {
    throw "Python virtual environment is missing. Run: npm run image:install"
}

if (-not (Test-Path $CheckpointRoot)) {
    New-Item -ItemType Directory -Path $CheckpointRoot | Out-Null
}

$CheckpointExtensions = @(".safetensors", ".ckpt", ".pt")
$CheckpointCount = @(Get-ChildItem -Path $CheckpointRoot -File -ErrorAction SilentlyContinue | Where-Object { $CheckpointExtensions -contains $_.Extension }).Count
if ($CheckpointCount -eq 0) {
    Write-Warning "No checkpoint model was found in $CheckpointRoot. ComfyUI can start, but image generation needs a model file."
}

$env:PYTHONUTF8 = "1"
$Url = "http://${HostAddress}:$Port"

Write-Host "Starting ComfyUI in CPU mode..."
Write-Host "Local URL: $Url"
Write-Host "CPU mode is expected to be slow on Intel UHD graphics."

if ($OpenBrowser) {
    Start-Process $Url
}

Push-Location $ComfyRoot
try {
    & $VenvPython "main.py" "--listen" $HostAddress "--port" "$Port" "--cpu"
} finally {
    Pop-Location
}

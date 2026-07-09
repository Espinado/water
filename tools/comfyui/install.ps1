param(
    [string]$RepositoryUrl = "https://github.com/comfyanonymous/ComfyUI.git",
    [switch]$SkipDependencies
)

$ErrorActionPreference = "Stop"
Set-StrictMode -Version Latest

$ToolRoot = $PSScriptRoot
$ComfyRoot = Join-Path $ToolRoot "ComfyUI"
$VenvRoot = Join-Path $ToolRoot ".venv"
$VenvPython = Join-Path $VenvRoot "Scripts\python.exe"

function Require-Command {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Name,
        [Parameter(Mandatory = $true)]
        [string]$InstallHint
    )

    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "$Name was not found. $InstallHint"
    }
}

function Select-Python {
    if (Get-Command "py" -ErrorAction SilentlyContinue) {
        foreach ($Version in @("3.12", "3.11")) {
            try {
                & py "-$Version" --version 1>$null 2>$null
                $FoundPython = ($LASTEXITCODE -eq 0)
            } catch {
                $FoundPython = $false
            }

            if ($FoundPython) {
                return @{
                    Command = "py"
                    Args = @("-$Version")
                    Display = "py -$Version"
                }
            }
        }
    }

    $VersionOutput = & python -c "import sys; print(f'{sys.version_info.major}.{sys.version_info.minor}')"
    if ($VersionOutput -in @("3.11", "3.12")) {
        return @{
            Command = "python"
            Args = @()
            Display = "python"
        }
    }

    throw "ComfyUI dependencies need Python 3.11 or 3.12. Found python $VersionOutput. Install Python 3.11/3.12 or make it available through the Windows py launcher."
}

Write-Host "Preparing ComfyUI local image generator..."
Write-Host "Target: $ComfyRoot"

Require-Command -Name "git" -InstallHint "Install Git for Windows from https://git-scm.com/download/win."
Require-Command -Name "python" -InstallHint "Install Python 3.11 or 3.12 from https://www.python.org/downloads/windows/ and enable 'Add python.exe to PATH'."
$Python = Select-Python
Write-Host "Using Python: $($Python.Display)"

if (Test-Path $ComfyRoot) {
    Write-Host "ComfyUI already exists. Updating with git pull..."
    git -C $ComfyRoot pull --ff-only
} else {
    Write-Host "Cloning ComfyUI..."
    git clone --depth 1 $RepositoryUrl $ComfyRoot
}

if (-not (Test-Path $VenvPython)) {
    Write-Host "Creating Python virtual environment..."
    & $Python.Command @($Python.Args) -m venv $VenvRoot
} else {
    Write-Host "Python virtual environment already exists."
}

if ($SkipDependencies) {
    Write-Host "Skipped dependency installation."
} else {
    Write-Host "Upgrading pip..."
    & $VenvPython -m pip install --upgrade pip

    Write-Host "Installing CPU PyTorch packages. This can take a while..."
    & $VenvPython -m pip install torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cpu

    Write-Host "Installing ComfyUI requirements..."
    & $VenvPython -m pip install -r (Join-Path $ComfyRoot "requirements.txt")
}

Write-Host ""
Write-Host "ComfyUI setup is ready."
Write-Host "Place checkpoint files in: $ComfyRoot\models\checkpoints"
Write-Host "Start it with: npm run image:start"
Write-Host "Local URL: http://127.0.0.1:8188"

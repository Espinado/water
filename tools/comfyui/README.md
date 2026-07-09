# Local ComfyUI Image Generator

This folder contains a Windows-friendly setup for running ComfyUI locally next to the Laravel app.

## Hardware note

This machine has Intel UHD graphics with very little dedicated VRAM, so ComfyUI is configured for CPU mode. It can start locally, but generation will be slow and may fail with large models or high resolutions.

For the best chance of success:

- Use small SD 1.5 checkpoints instead of SDXL.
- Start with `512x512` or smaller images.
- Use low step counts such as `10` to `20`.
- Avoid running other heavy apps while generating.

## Requirements

- Windows PowerShell.
- Git for Windows.
- Python 3.11 or 3.12 available as `python` in PATH.
- Free disk space for Python packages and model files. CPU PyTorch and ComfyUI dependencies can use several GB.

## Install

From the project root:

```powershell
npm run image:install
```

The installer will:

- Clone ComfyUI into `tools/comfyui/ComfyUI`.
- Create a Python virtual environment in `tools/comfyui/.venv`.
- Install CPU PyTorch and ComfyUI requirements.

If you only want to clone ComfyUI and create the virtual environment without installing dependencies:

```powershell
powershell -ExecutionPolicy Bypass -File tools/comfyui/install.ps1 -SkipDependencies
```

## Add a model

Place a checkpoint file in:

```text
tools/comfyui/ComfyUI/models/checkpoints
```

Supported checkpoint extensions include `.safetensors`, `.ckpt`, and `.pt`.

This setup does not commit model files to git. Models are large and must stay local.

## Start

From the project root:

```powershell
npm run image:start
```

Then open:

```text
http://127.0.0.1:8188
```

You can also open the browser automatically:

```powershell
powershell -ExecutionPolicy Bypass -File tools/comfyui/start.ps1 -OpenBrowser
```

## Troubleshooting

If `python` is not found, install Python from the official Windows installer and enable `Add python.exe to PATH`.

If dependency installation fails, rerun:

```powershell
npm run image:install
```

If generation is extremely slow, that is expected in CPU mode. Use a smaller model, lower resolution, and fewer steps.

If ComfyUI starts but cannot generate, confirm that a checkpoint exists in `tools/comfyui/ComfyUI/models/checkpoints`.

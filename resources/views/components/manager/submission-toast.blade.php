<div
    x-data="{
        show: false,
        message: '',
        timer: null,
        showToast(msg) {
            this.message = msg;
            this.show = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.show = false, 5000);
        },
    }"
    @manager-submission-toast.window="showToast($event.detail.message)"
    class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex justify-center sm:inset-x-auto sm:end-6 sm:justify-end"
>
    <div
        x-show="show"
        x-transition
        x-cloak
        class="pointer-events-auto max-w-sm rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 shadow-lg"
        style="display: none;"
    >
        <p x-text="message"></p>
    </div>
</div>

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
    class="pointer-events-none fixed inset-x-4 bottom-24 z-50 flex justify-center sm:inset-x-auto sm:bottom-4 sm:end-6 sm:justify-end lg:bottom-4"
>
    <div
        x-show="show"
        x-transition
        x-cloak
        class="pointer-events-auto max-w-md rounded-2xl border border-k16-success/30 bg-k16-success-soft px-5 py-4 text-base font-semibold text-k16-success"
        style="display: none;"
    >
        <p x-text="message"></p>
    </div>
</div>

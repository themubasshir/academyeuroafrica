document.addEventListener('alpine:init', () => {
    window.Alpine.data('modal', () => ({
        dirty: false,
        returnFocus: null,

        syncVisibility(isOpen) {
            if (isOpen) {
                this.returnFocus = document.activeElement;
                this.dirty = false;
                this.$nextTick(() => {
                    this.$refs.panel.querySelector('[autofocus], input, select, textarea, button')?.focus();
                });

                return;
            }

            this.dirty = false;

            if (this.returnFocus) {
                this.$nextTick(() => this.returnFocus?.focus());
            }
        },

        markDirty(isOpen) {
            if (isOpen) {
                this.dirty = true;
            }
        },

        requestClose(isOpen) {
            if (isOpen) {
                this.close();
            }
        },

        close() {
            const shouldConfirm = this.$root.dataset.confirmClose === 'true' && this.dirty;

            if (shouldConfirm && ! window.confirm('Discard your unsaved changes?')) {
                return;
            }

            this.dirty = false;
            this.$wire.$set(this.$root.dataset.openProperty, false, false);
            this.$wire[this.$root.dataset.closeMethod]();
        },
    }));
});

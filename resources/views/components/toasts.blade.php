@props(['position' => 'bottom-right', 'duration' => 4000, 'stack' => true, 'max' => 5, 'progress' => true])

{{--
    Place once in your layout. Fire a toast from anywhere:
      Alpine:   $dispatch('toast', { title: 'Salvo', variant: 'success' })
      JS:       window.dispatchEvent(new CustomEvent('toast', { detail: { title: 'Salvo' } }))
      Livewire: $this->dispatch('toast', title: 'Salvo', variant: 'success')
      Session:  session()->flash('toast', ['title' => 'Salvo'])
    Options per toast: title, description, variant (info|success|warning|error), duration (ms, 0 = stays), dismissible, progress.
    progress: a bar on the bottom edge drains until the toast closes; it pauses with the timer while hovered.
--}}
<div popover="manual" role="region" aria-label="Notificações"
    x-data="{
        toasts: [],
        position: @js($position),
        duration: @js((int) $duration),
        stack: @js((bool) $stack),
        max: @js((int) $max),
        progress: @js((bool) $progress),
        hovering: false,
        sequence: 0,
        get top() { return this.position.startsWith('top') },
        get ordered() { return this.top ? [...this.toasts].reverse() : this.toasts },
        get expanded() { return ! this.stack || this.hovering },
        add(detail) {
            const created = { id: ++this.sequence, title: '', description: '', variant: 'info', duration: this.duration, dismissible: true, progress: this.progress, ...detail, leaving: false, timer: null, remaining: 0, started: 0 };
            this.toasts.push(created);
            const toast = this.toasts[this.toasts.length - 1]; {{-- the reactive proxy, so timer changes reach the bindings --}}
            this.toasts.filter((item) => ! item.leaving).slice(0, -this.max).forEach((item) => this.remove(item.id));
            toast.remaining = toast.duration;
            if (! this.hovering) this.resume(toast);
            this.$el.hidePopover(); this.$el.showPopover(); {{-- re-enter the top layer above any open dialog --}}
        },
        resume(toast) { if (! toast.duration || toast.leaving) return; toast.started = Date.now(); toast.timer = setTimeout(() => this.remove(toast.id), toast.remaining) },
        pause(toast) { if (! toast.timer) return; clearTimeout(toast.timer); toast.timer = null; toast.remaining -= Date.now() - toast.started },
        remove(id) {
            const toast = this.toasts.find((item) => item.id === id);
            if (! toast || toast.leaving) return;
            clearTimeout(toast.timer);
            toast.leaving = true;
            setTimeout(() => (this.toasts = this.toasts.filter((item) => item.id !== id)), 220);
        },
        depth(toast) { const live = this.toasts.filter((item) => ! item.leaving); return live.length - 1 - live.indexOf(toast) },
        style(toast) {
            if (this.expanded) return '';
            const depth = Math.max(0, this.depth(toast)), direction = this.top ? 1 : -1;
            return `transform: translateY(${direction * depth * 12}px) scale(${1 - depth * 0.05}); opacity: ${depth > 2 ? 0 : 1}; z-index: ${50 - depth};`;
        },
    }"
    x-init="$el.showPopover(); @if (session('toast')) add(@js(session('toast'))); @endif"
    x-on:toast.window="add($event.detail)"
    x-on:toast-config.window="Object.assign($data, $event.detail)"
    x-on:mouseenter="hovering = true; toasts.forEach((toast) => pause(toast))"
    x-on:mouseleave="hovering = false; toasts.forEach((toast) => resume(toast))"
    x-bind:class="{
        'top-4': top, 'bottom-4': ! top,
        'left-4': position.endsWith('left'), 'right-4': position.endsWith('right'),
        'left-1/2 -translate-x-1/2': position.endsWith('center'),
    }"
    class="fixed m-0 inset-auto p-0 bg-transparent overflow-visible w-[min(24rem,calc(100vw-2rem))] pointer-events-none">
    <ol x-bind:class="expanded ? 'flex flex-col gap-2' : 'grid'" class="pointer-events-auto">
        <template x-for="toast in ordered" x-bind:key="toast.id">
            <li x-bind:role="toast.variant === 'error' ? 'alert' : 'status'" aria-atomic="true"
                x-bind:style="style(toast)"
                x-bind:class="[
                    expanded ? '' : '[grid-area:1/1] ' + (top ? 'self-start' : 'self-end'),
                    ! expanded && depth(toast) > 0 ? 'nui-toast-behind' : '',
                    toast.leaving ? 'opacity-0 scale-95 ' + (top ? '-translate-y-2' : 'translate-y-2') : '',
                ]"
                class="nui-toast relative overflow-hidden flex items-start gap-3 p-4 bg-base-100 text-base-content rounded-nui border-[length:var(--nui-border-width)] border-base-300 shadow-[0_1px_2px_oklch(0%_0_0/0.06),0_16px_36px_-14px_oklch(0%_0_0/0.35)] transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]"
                x-bind:data-side="top ? 'top' : 'bottom'">
                <span aria-hidden="true" class="mt-px grid place-items-center size-5 shrink-0 rounded-full"
                    x-bind:class="{
                        'bg-primary text-primary-content': toast.variant === 'info',
                        'bg-[oklch(55%_0.14_150)] text-white': toast.variant === 'success',
                        'bg-[oklch(78%_0.15_80)] text-[oklch(25%_0.05_70)]': toast.variant === 'warning',
                        'bg-danger text-danger-content': toast.variant === 'error',
                    }">
                    <svg x-show="toast.variant === 'success'" class="size-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 8.5 3 3 6-7" /></svg>
                    <svg x-show="toast.variant === 'error'" class="size-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="m4.5 4.5 7 7M11.5 4.5l-7 7" /></svg>
                    <svg x-show="toast.variant === 'warning'" class="size-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M8 3.5v5.5M8 12.25v.25" /></svg>
                    <svg x-show="toast.variant === 'info'" class="size-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M8 7v5.5M8 3.75V4" /></svg>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold leading-snug" x-text="toast.title"></p>
                    <p x-show="toast.description" class="mt-0.5 text-sm text-base-content/65" x-text="toast.description"></p>
                </div>
                <x-nui::button variant="ghost" size="sm" type="button" x-show="toast.dismissible" x-on:click="remove(toast.id)" aria-label="Fechar notificação"
                    class="!p-0 -mr-1.5 -mt-1 grid place-items-center !size-7 shrink-0 rounded-[calc(var(--nui-radius)-4px)] text-base-content/50 hover:text-base-content hover:bg-base-content/[0.06] focus-visible:outline-2 focus-visible:outline-primary">
                    <svg aria-hidden="true" class="size-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m4 4 8 8M12 4l-8 8" /></svg>
                </x-nui::button>
                <span x-show="toast.progress && toast.duration > 0" aria-hidden="true" class="absolute inset-x-0 bottom-0 h-[3px] bg-base-content/[0.06]">
                    <span class="nui-toast-timer block h-full origin-left"
                        x-bind:style="`animation-duration: ${toast.duration}ms; animation-play-state: ${toast.timer ? 'running' : 'paused'}`"
                        x-bind:class="{
                            'bg-primary': toast.variant === 'info',
                            'bg-[oklch(55%_0.14_150)]': toast.variant === 'success',
                            'bg-[oklch(72%_0.16_75)]': toast.variant === 'warning',
                            'bg-danger': toast.variant === 'error',
                        }"></span>
                </span>
            </li>
        </template>
    </ol>
</div>



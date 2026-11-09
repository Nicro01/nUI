import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import DOMPurify from 'dompurify';

const sanitize = (html) => DOMPurify.sanitize(String(html ?? ''), {
    ALLOWED_TAGS: ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'h2', 'h3', 'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'a', 'hr'],
    ALLOWED_ATTR: ['href', 'target', 'rel', 'start'],
    ALLOW_DATA_ATTR: false,
});

// Keep the ProseMirror instance in a closure: Alpine must never proxy its state.
window.nuiMountRichText = (state, options) => {
    let editor;
    let resetTimer;
    const initialValue = state.value;
    const editable = () => !state.disabled && !state.readOnly;
    const refresh = (instance = editor) => {
        state.active = Object.fromEntries(['bold', 'italic', 'underline', 'strike', 'bulletList', 'orderedList', 'blockquote', 'codeBlock', 'link']
            .map((mark) => [mark, instance.isActive(mark)]));
        state.active.heading2 = instance.isActive('heading', { level: 2 });
        state.active.heading3 = instance.isActive('heading', { level: 3 });
        state.canUndo = instance.can().undo();
        state.canRedo = instance.can().redo();
        state.words = instance.getText().trim().split(/\s+/u).filter(Boolean).length;
    };

    editor = new Editor({
        element: state.$refs.editor,
        content: sanitize(state.value),
        editable: editable(),
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3] },
                link: { openOnClick: false, autolink: false, HTMLAttributes: { target: null, rel: 'noopener noreferrer' } },
            }),
            Placeholder.configure({ placeholder: options.placeholder }),
        ],
        editorProps: {
            attributes: {
                id: options.id,
                role: 'textbox',
                'aria-label': options.label,
                'aria-multiline': 'true',
                'aria-invalid': options.invalid ? 'true' : 'false',
                ...(options.describedBy ? { 'aria-describedby': options.describedBy } : {}),
                class: 'nui-rich-text-content',
            },
            transformPastedHTML: sanitize,
        },
        onUpdate: ({ editor: instance }) => {
            state.value = instance.isEmpty ? '' : instance.getHTML();
            state.$dispatch('rich-text-change', { html: state.value, text: instance.getText() });
        },
        onTransaction: ({ editor: instance }) => refresh(instance),
        onSelectionUpdate: ({ editor: instance }) => refresh(instance),
    });

    const setValue = (value) => {
        if (editor.isDestroyed) return;
        const html = sanitize(value);
        if (html !== editor.getHTML()) editor.commands.setContent(html, { emitUpdate: false });
        const normalized = editor.isEmpty ? '' : editor.getHTML();
        if (state.value !== normalized) state.value = normalized;
        refresh();
    };
    const form = state.$el.closest('form');
    const reset = () => { resetTimer = setTimeout(() => setValue(state.value = initialValue), 0); };
    form?.addEventListener('reset', reset);
    setValue(state.value);
    state.ready = true;

    const syncAttributes = () => {
        const { invalid, description, disabled, readonly } = state.$el.dataset;
        state.disabled = disabled === 'true';
        state.readOnly = readonly === 'true';
        editor.view.dom.setAttribute('aria-invalid', invalid);
        if (description) editor.view.dom.setAttribute('aria-describedby', description);
        else editor.view.dom.removeAttribute('aria-describedby');
        const box = state.$el.querySelector('.nui-rich-text-box');
        box?.classList.toggle('border-danger', invalid === 'true');
        box?.classList.toggle('border-field-border', invalid !== 'true');
    };
    const attributes = new MutationObserver(syncAttributes);
    attributes.observe(state.$el, { attributes: true, attributeFilter: ['data-invalid', 'data-description', 'data-disabled', 'data-readonly'] });
    syncAttributes();

    return {
        setValue,
        focus: () => editor.commands.focus(),
        setEditable() {
            editor.setEditable(editable());
            editor.view.dom.setAttribute('aria-disabled', String(state.disabled));
            editor.view.dom.setAttribute('aria-readonly', String(state.readOnly));
            if (!editable()) state.linkOpen = false;
        },
        run(action) {
            if (!editable()) return;
            const chain = editor.chain().focus();
            const commands = {
                bold: () => chain.toggleBold().run(), italic: () => chain.toggleItalic().run(),
                underline: () => chain.toggleUnderline().run(), strike: () => chain.toggleStrike().run(),
                heading2: () => chain.toggleHeading({ level: 2 }).run(), heading3: () => chain.toggleHeading({ level: 3 }).run(),
                bulletList: () => chain.toggleBulletList().run(), orderedList: () => chain.toggleOrderedList().run(),
                blockquote: () => chain.toggleBlockquote().run(), codeBlock: () => chain.toggleCodeBlock().run(),
                undo: () => chain.undo().run(), redo: () => chain.redo().run(),
            };
            commands[action]?.();
        },
        openLink() {
            if (!editable()) return;
            state.url = editor.getAttributes('link').href || '';
            state.linkError = '';
            state.linkOpen = true;
            state.$nextTick(() => state.$el.querySelector('[data-link-input]')?.focus());
        },
        saveLink() {
            if (!editable()) return;
            const href = state.url.trim();
            try {
                if (!href || !['http:', 'https:', 'mailto:', 'tel:'].includes(new URL(href, window.location.href).protocol)) throw new Error('protocol');
            } catch {
                state.linkError = 'Use um endereço http, https, mailto ou tel válido.';
                return;
            }
            editor.chain().focus().extendMarkRange('link').setLink({ href }).run();
            state.linkOpen = false;
        },
        removeLink() {
            if (!editable()) return;
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
            state.linkOpen = false;
        },
        destroy() {
            attributes.disconnect();
            clearTimeout(resetTimer);
            form?.removeEventListener('reset', reset);
            editor.destroy();
        },
    };
};

window.dispatchEvent(new Event('nui-rich-text-ready'));

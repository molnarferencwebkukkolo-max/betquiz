import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import { Table } from '@tiptap/extension-table';
import TableRow from '@tiptap/extension-table-row';
import TableHeader from '@tiptap/extension-table-header';
import TableCell from '@tiptap/extension-table-cell';
import Image from '@tiptap/extension-image';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-content-editor-form]').forEach((form) => {
        const target = form.querySelector('[data-content-editor]');
        let initialContent = form.querySelector('[data-initial-html]')?.innerHTML || '<p></p>';
        try {
            const parsed = JSON.parse(form.querySelector('[data-initial-content]')?.textContent || 'null');
            if (parsed) initialContent = parsed;
        } catch (_) {}

        const editor = new Editor({
            element: target, content: initialContent,
            extensions: [StarterKit.configure({ link: false, underline: false }), Link.configure({ openOnClick: false, autolink: true }), Underline,
                TextAlign.configure({ types: ['heading', 'paragraph'] }), Table.configure({ resizable: true }), TableRow, TableHeader, TableCell, Image.configure({ allowBase64: false })],
            editorProps: { attributes: { class: 'content-editor-prose' } },
        });

        const run = (command) => {
            const chain = editor.chain().focus();
            const actions = {
                bold:()=>chain.toggleBold().run(), italic:()=>chain.toggleItalic().run(), underline:()=>chain.toggleUnderline().run(), strike:()=>chain.toggleStrike().run(),
                h2:()=>chain.toggleHeading({level:2}).run(), h3:()=>chain.toggleHeading({level:3}).run(), bulletList:()=>chain.toggleBulletList().run(),
                orderedList:()=>chain.toggleOrderedList().run(), blockquote:()=>chain.toggleBlockquote().run(), undo:()=>chain.undo().run(), redo:()=>chain.redo().run(),
                alignLeft:()=>chain.setTextAlign('left').run(), alignCenter:()=>chain.setTextAlign('center').run(), alignRight:()=>chain.setTextAlign('right').run(),
                table:()=>chain.insertTable({rows:3,cols:3,withHeaderRow:true}).run(),
                link:()=>{const old=editor.getAttributes('link').href||'';const href=window.prompt('Hivatkozás URL-je:',old);if(href===null)return;if(!href)chain.unsetLink().run();else chain.extendMarkRange('link').setLink({href,target:'_blank'}).run();},
            };
            actions[command]?.();
        };
        form.querySelectorAll('[data-editor-command]').forEach((button)=>button.addEventListener('click',()=>run(button.dataset.editorCommand)));

        const imageInput=form.querySelector('[data-editor-image-input]');
        form.querySelector('[data-editor-image]')?.addEventListener('click',()=>imageInput?.click());
        imageInput?.addEventListener('change',async()=>{
            const file=imageInput.files?.[0];if(!file)return;const body=new FormData();body.append('image',file);
            const response=await fetch(target.dataset.uploadUrl,{method:'POST',body,headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||'','Accept':'application/json'}});
            if(!response.ok){window.alert('A kép feltöltése nem sikerült.');return;}const result=await response.json();
            editor.chain().focus().setImage({src:result.url,alt:file.name}).run();imageInput.value='';
        });
        form.addEventListener('submit',()=>{form.querySelector('[data-content-json]').value=JSON.stringify(editor.getJSON());form.querySelector('[data-content-html]').value=editor.getHTML();});
    });
});

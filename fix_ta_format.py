"""
Script otomatis untuk memformat dokumen Tugas Akhir sesuai panduan STMIK MD 2026.
Aturan formatisasi:
- Margin: kiri 4cm, kanan 3cm, atas 4cm, bawah 3cm (biasanya sudah benar)
- Font isi: Times New Roman, Regular, 12pt
- Font judul Bab: Times New Roman, Bold, 14pt
- Font sub-bab: Times New Roman, Bold, 12pt
- Spasi: 1.5 untuk semua isi, 1 spasi untuk Abstrak
- Indentasi paragraf pertama: 1.25 cm
- Istilah asing: Italic
- Judul tabel/gambar: Times New Roman 11pt
"""

import shutil
import os
from docx import Document
from docx.shared import Pt, Cm, RGBColor
from docx.enum.text import WD_LINE_SPACING, WD_ALIGN_PARAGRAPH
from docx.oxml.ns import qn
from docx.oxml import OxmlElement
import copy

INPUT_PATH = '/opt/lampp/htdocs/sistempengaduan/Tugas Akhir.docx'
OUTPUT_PATH = '/opt/lampp/htdocs/sistempengaduan/Tugas Akhir_Fixed.docx'

# Backup original
shutil.copy2(INPUT_PATH, INPUT_PATH.replace('.docx', '_BACKUP.docx'))
print("✓ Backup dibuat")

doc = Document(INPUT_PATH)

# =============================================
# 1. FIX PAGE MARGINS (sudah benar, validasi ulang)
# =============================================
for sec in doc.sections:
    sec.left_margin = Cm(4)
    sec.right_margin = Cm(3)
    sec.top_margin = Cm(4)
    sec.bottom_margin = Cm(3)
print("✓ Margin halaman: kiri=4cm, kanan=3cm, atas=4cm, bawah=3cm")

# =============================================
# 2. Identify page sections (cover, front matter, body)
# =============================================
def is_cover_area(idx):
    """Cover: before Surat Pernyataan. Keep as-is for cover formatting."""
    return idx < 115

def is_heading1(p):
    return p.style.name in ('Heading 1', 'Heading1') or \
           (p.style.name == 'normal' and p.runs and any(
               r.font.size and r.font.size.pt == 14 and r.bold for r in p.runs
           ) and len(p.text.strip()) < 60 and p.text.strip().startswith('BAB'))

def is_heading2(p):
    return p.style.name in ('Heading 2', 'Heading2', 'Heading 3', 'Heading 4')

def looks_like_subheading(p):
    """Sub-bab: numbered like 1.1, 2.1, etc."""
    t = p.text.strip()
    if not t: return False
    parts = t.split()
    if not parts: return False
    return (parts[0][0].isdigit() and '.' in parts[0]) or p.style.name in ('Heading 2','Heading 3','Heading 4','Heading2','Heading3','Heading4')

def is_body_text(p):
    return p.style.name in ('normal', 'Normal', 'Body Text', 'Body Text Indent')

def is_table_or_figure_caption(p):
    t = p.text.strip().lower()
    return t.startswith('tabel') or t.startswith('gambar') or t.startswith('sumber:') or t.startswith('sumber :')

def is_code_block(p):
    """Listing code: use Courier New"""
    t = p.text.strip()
    code_starts = ['SELECT', 'INSERT', 'CREATE', 'DROP', 'ALTER', 'UPDATE', 'DELETE', 
                   'function', 'class', 'def ', 'int ', 'void ', 'if (', 'for (', 
                   '$', '<?php', '<?', '?>', '</']
    return any(t.startswith(s) for s in code_starts) or \
           (p.runs and any(r.font.name == 'Courier New' for r in p.runs if r.font.name))

def set_run_font(run, name='Times New Roman', size_pt=12, bold=None, italic=None):
    if name:
        run.font.name = name
        # For compatibility with Word, set the font for East Asian and Complex Script too
        rPr = run._r.get_or_add_rPr()
        # Set ascii and hAnsi
    if size_pt:
        run.font.size = Pt(size_pt)
    if bold is not None:
        run.font.bold = bold
    if italic is not None:
        run.font.italic = italic

def set_paragraph_spacing(p, line_spacing=1.5, space_before_pt=0, space_after_pt=0):
    pf = p.paragraph_format
    pf.line_spacing_rule = WD_LINE_SPACING.MULTIPLE
    pf.line_spacing = line_spacing
    from docx.shared import Pt
    if space_before_pt is not None:
        pf.space_before = Pt(space_before_pt)
    if space_after_pt is not None:
        pf.space_after = Pt(space_after_pt)

def fix_paragraph_indent(p, first_line_cm=1.25):
    pf = p.paragraph_format
    # Remove negative hanging indents
    if pf.first_line_indent is not None and pf.first_line_indent.cm < 0:
        pf.first_line_indent = Cm(first_line_cm)
    elif pf.first_line_indent is None and first_line_cm > 0:
        pf.first_line_indent = Cm(first_line_cm)

stats = {'body_fixed': 0, 'heading_fixed': 0, 'subheading_fixed': 0, 'caption_fixed': 0, 'code_fixed': 0, 'skipped_cover': 0}

is_in_abstrak = False

for idx, p in enumerate(doc.paragraphs):
    text = p.text.strip()
    
    if not text:
        continue
    
    # Skip cover/front matter for deeper formatting changes    
    if is_cover_area(idx):
        stats['skipped_cover'] += 1
        continue

    # Detect abstrak section
    if text.upper() in ('ABSTRAK', 'ABSTRACT'):
        is_in_abstrak = True
    elif text.upper().startswith('BAB I') and 'PENDAHULUAN' in text.upper():
        is_in_abstrak = False
    elif text.upper() in ('KATA PENGANTAR', 'DAFTAR ISI', 'DAFTAR GAMBAR', 'DAFTAR TABEL'):
        is_in_abstrak = False

    # 1. BAB headings (Heading 1)
    if p.style.name == 'Heading 1' or (text.startswith('BAB') and len(text) < 50):
        for r in p.runs:
            set_run_font(r, 'Times New Roman', 14, bold=True, italic=False)
        pf = p.paragraph_format
        pf.alignment = WD_ALIGN_PARAGRAPH.CENTER
        set_paragraph_spacing(p, line_spacing=1.0, space_before_pt=0, space_after_pt=0)
        pf.first_line_indent = None
        stats['heading_fixed'] += 1
        continue

    # 2. Sub-bab headings (Heading 2/3/4)
    if p.style.name in ('Heading 2', 'Heading 3', 'Heading 4'):
        for r in p.runs:
            set_run_font(r, 'Times New Roman', 12, bold=True, italic=False)
        pf = p.paragraph_format
        pf.alignment = WD_ALIGN_PARAGRAPH.LEFT
        set_paragraph_spacing(p, line_spacing=1.5, space_before_pt=12, space_after_pt=6)
        pf.first_line_indent = None
        stats['subheading_fixed'] += 1
        continue

    # 3. Caption tabel/gambar
    if is_table_or_figure_caption(p):
        for r in p.runs:
            set_run_font(r, 'Times New Roman', 11, bold=None)
        pf = p.paragraph_format
        pf.alignment = WD_ALIGN_PARAGRAPH.CENTER
        set_paragraph_spacing(p, line_spacing=1.0, space_before_pt=6, space_after_pt=6)
        pf.first_line_indent = None
        stats['caption_fixed'] += 1
        continue

    # 4. Code blocks
    if is_code_block(p):
        for r in p.runs:
            set_run_font(r, 'Courier New', 10, bold=None)
        set_paragraph_spacing(p, line_spacing=1.0, space_before_pt=0, space_after_pt=0)
        stats['code_fixed'] += 1
        continue

    # 5. Body text (normal paragraphs)
    if p.style.name in ('normal', 'Normal', 'Body Text', 'Body Text Indent', 'List Paragraph'):
        line_sp = 1.0 if is_in_abstrak else 1.5
        for r in p.runs:
            # Preserve bold/italic but fix font
            if r.font.name != 'Courier New':  # don't touch code fonts
                is_bold = r.bold
                is_italic = r.italic
                set_run_font(r, 'Times New Roman', 12, bold=is_bold, italic=is_italic)
        pf = p.paragraph_format
        set_paragraph_spacing(p, line_spacing=line_sp, space_before_pt=0, space_after_pt=0)
        
        # Fix alignment
        if pf.alignment not in (WD_ALIGN_PARAGRAPH.JUSTIFY, WD_ALIGN_PARAGRAPH.CENTER, WD_ALIGN_PARAGRAPH.RIGHT):
            pf.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        
        # Fix negative/missing first line indent for body paragraphs that are not list items
        if p.style.name in ('normal', 'Normal', 'Body Text'):
            if pf.first_line_indent is not None and pf.first_line_indent.cm < 0:
                pf.first_line_indent = Cm(1.25)
        
        stats['body_fixed'] += 1
        continue

doc.save(OUTPUT_PATH)
print(f"\n✅ Dokumen selesai diformat dan disimpan ke:")
print(f"   {OUTPUT_PATH}")
print(f"\nRingkasan perubahan:")
for k, v in stats.items():
    print(f"   {k}: {v} paragraf")

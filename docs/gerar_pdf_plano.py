#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Converte PLANO_DE_NEGOCIO_FAUCET.md em um PDF com layout de relatorio.
ReportLab puro (sem dependencias de sistema).
"""
import re
import os

from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm
from reportlab.lib import colors
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.enums import TA_LEFT, TA_CENTER, TA_JUSTIFY
from reportlab.platypus import (
    BaseDocTemplate, PageTemplate, Frame, Paragraph, Spacer, Table, TableStyle,
    PageBreak, KeepTogether, Flowable, NextPageTemplate,
)
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

# ---------------------------------------------------------------- fontes
FONT_DIR = "/usr/share/fonts/truetype/dejavu"
pdfmetrics.registerFont(TTFont("DJV", f"{FONT_DIR}/DejaVuSans.ttf"))
pdfmetrics.registerFont(TTFont("DJV-B", f"{FONT_DIR}/DejaVuSans-Bold.ttf"))
# nao ha variantes italicas instaladas: reaproveita normal/bold
pdfmetrics.registerFontFamily("DJV", normal="DJV", bold="DJV-B", italic="DJV", boldItalic="DJV-B")

# ---------------------------------------------------------------- paleta
INK     = colors.HexColor("#0B1220")
NAVY    = colors.HexColor("#12304F")
ACCENT  = colors.HexColor("#B45309")
GOLD    = colors.HexColor("#D97706")
RED     = colors.HexColor("#B91C1C")
RED_BG  = colors.HexColor("#FEF2F2")
GREEN   = colors.HexColor("#047857")
GREEN_BG= colors.HexColor("#ECFDF5")
MUTED   = colors.HexColor("#5A6B80")
LIGHT   = colors.HexColor("#F4F6F9")
BORDER  = colors.HexColor("#D3DAE3")
WHITE   = colors.white
BLUE_BG = colors.HexColor("#EFF6FF")
CREAM   = colors.HexColor("#FBF7F0")

PAGE_W, PAGE_H = A4
M_L, M_R, M_T, M_B = 2.0 * cm, 2.0 * cm, 2.0 * cm, 1.9 * cm
CONTENT_W = PAGE_W - M_L - M_R

# ---------------------------------------------------------------- estilos
S = {}
S["body"] = ParagraphStyle("body", fontName="DJV", fontSize=9.4, leading=15.2,
                           textColor=INK, alignment=TA_JUSTIFY, spaceAfter=6.5,
                           splitLongWords=1)
S["body_l"] = ParagraphStyle("body_l", parent=S["body"], alignment=TA_LEFT)
S["h1"] = ParagraphStyle("h1", fontName="DJV-B", fontSize=17.5, leading=21,
                         textColor=NAVY, spaceBefore=2, spaceAfter=3)
S["h1num"] = ParagraphStyle("h1num", fontName="DJV-B", fontSize=9, leading=11,
                            textColor=GOLD, spaceAfter=1.5)
S["h2"] = ParagraphStyle("h2", fontName="DJV-B", fontSize=11.6, leading=15,
                         textColor=NAVY, spaceBefore=12, spaceAfter=4.5)
S["h3"] = ParagraphStyle("h3", fontName="DJV-B", fontSize=9.9, leading=13.5,
                         textColor=ACCENT, spaceBefore=8, spaceAfter=3)
S["bullet"] = ParagraphStyle("bullet", parent=S["body"], leftIndent=13,
                             bulletIndent=2, spaceAfter=3.2, alignment=TA_LEFT)
S["num"] = ParagraphStyle("num", parent=S["body"], leftIndent=16,
                          bulletIndent=2, spaceAfter=3.2, alignment=TA_LEFT)
S["cell"] = ParagraphStyle("cell", fontName="DJV", fontSize=8.2, leading=11.4,
                           textColor=INK, alignment=TA_LEFT, splitLongWords=1)
S["cell_h"] = ParagraphStyle("cell_h", fontName="DJV-B", fontSize=8.2, leading=11.4,
                             textColor=WHITE, alignment=TA_LEFT, splitLongWords=1)
S["quote"] = ParagraphStyle("quote", fontName="DJV", fontSize=9.0, leading=13.8,
                            textColor=INK, alignment=TA_LEFT, splitLongWords=1)

# ---------------------------------------------------------------- emoji
KEEP = set("☐✓⚠→←↑↓×÷≈−●▸▲■·—–≥≤≠±§ºª")

def limpar(s):
    out = []
    for ch in s:
        o = ord(ch)
        if ch in KEEP:
            out.append(ch); continue
        if (0x1F000 <= o <= 0x1FAFF) or (0x2600 <= o <= 0x27BF) or \
           (0x2B00 <= o <= 0x2BFF) or (0xFE00 <= o <= 0xFE0F) or \
           o in (0x200D, 0x20E3) or (0x1F1E6 <= o <= 0x1F1FF):
            continue
        out.append(ch)
    s = "".join(out)
    s = re.sub(r"[ \t]{2,}", " ", s)
    s = re.sub(r"\(\s*\)", "", s)
    s = re.sub(r"^\s*[:·]\s*", "", s)
    return s.strip()

# ---------------------------------------------------------------- inline
BOLD_RE = re.compile(r"\*\*(.+?)\*\*", re.S)

def esc(t):
    return t.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")

def inline(t):
    t = limpar(t)
    t = esc(t)
    t = BOLD_RE.sub(lambda m: "<b>" + m.group(1) + "</b>", t)
    t = re.sub(r"(?<!\*)\*([^*\n]+?)\*(?!\*)", r"\1", t)
    # links clicaveis
    def _link(m):
        url = m.group(1)
        curto = url.replace("https://", "").replace("http://", "").replace("www.", "")
        if len(curto) > 58:
            curto = curto[:55] + "..."
        return (f'<link href="{url}" color="#1D4ED8"><u>{curto}</u></link>'
                f' <font color="#8A97A6" size="7">↗</font>')
    t = re.sub(r"(https?://[^\s<|]+)", _link, t)
    t = re.sub(r"`([^`]+?)`", r'<font name="DJV" color="#B45309">\1</font>', t)
    t = t.replace("  ", " ")
    return t

# ---------------------------------------------------------------- flowables
class HRule(Flowable):
    def __init__(self, w=CONTENT_W, color=BORDER, thick=0.7, pad=5):
        Flowable.__init__(self)
        self.w, self.c, self.t, self.pad = w, color, thick, pad
    def wrap(self, aw, ah):
        self.width = self.w
        return (self.w, self.t + 2 * self.pad)
    def draw(self):
        self.canv.setStrokeColor(self.c)
        self.canv.setLineWidth(self.t)
        self.canv.line(0, self.pad, self.w, self.pad)


class SectionMark(Flowable):
    """Marca a pagina de um heading para o sumario (2 passadas)."""
    def __init__(self, key):
        Flowable.__init__(self)
        self.key = key
        self.page = None
    def wrap(self, aw, ah):
        return (0, 0)
    def draw(self):
        self.page = self.canv.getPageNumber()


def callout(texto, tipo="warn"):
    cores = {
        "warn":  (RED, RED_BG),
        "info":  (colors.HexColor("#1D4ED8"), BLUE_BG),
        "ok":    (GREEN, GREEN_BG),
        "key":   (GOLD, CREAM),
    }
    bar, bg = cores[tipo]
    p = Paragraph(texto, S["quote"])
    t = Table([[p]], colWidths=[CONTENT_W])
    t.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, -1), bg),
        ("LINEBEFORE", (0, 0), (0, -1), 3.2, bar),
        ("LEFTPADDING", (0, 0), (-1, -1), 10),
        ("RIGHTPADDING", (0, 0), (-1, -1), 9),
        ("TOPPADDING", (0, 0), (-1, -1), 7),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 7),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
    ]))
    return t


def tabela(linhas, header=True):
    ncols = max(len(r) for r in linhas)
    linhas = [r + [""] * (ncols - len(r)) for r in linhas]

    # largura por conteudo
    pesos = []
    for c in range(ncols):
        mx = 1
        for r in linhas:
            mx = max(mx, len(limpar(r[c])))
        pesos.append(min(max(mx, 6), 62) ** 0.92)
    soma = sum(pesos)
    larg = [max(2.1 * cm, CONTENT_W * p / soma) for p in pesos]
    fator = CONTENT_W / sum(larg)
    larg = [w * fator for w in larg]

    dados = []
    for i, r in enumerate(linhas):
        est = S["cell_h"] if (header and i == 0) else S["cell"]
        dados.append([Paragraph(inline(x), est) for x in r])

    t = Table(dados, colWidths=larg, repeatRows=1 if header else 0, hAlign="LEFT")
    cmds = [
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 5.5),
        ("RIGHTPADDING", (0, 0), (-1, -1), 5.5),
        ("TOPPADDING", (0, 0), (-1, -1), 4.6),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 4.6),
        ("GRID", (0, 0), (-1, -1), 0.45, BORDER),
    ]
    if header:
        cmds += [
            ("BACKGROUND", (0, 0), (-1, 0), NAVY),
            ("LINEBELOW", (0, 0), (-1, 0), 0, WHITE),
        ]
        for i in range(1, len(dados)):
            if i % 2 == 0:
                cmds.append(("BACKGROUND", (0, i), (-1, i), LIGHT))
    t.setStyle(TableStyle(cmds))
    return t


# ---------------------------------------------------------------- parser
def parse(md, toc_pages=None):
    """Retorna (flowables, toc_entries)."""
    linhas = md.split("\n")
    out = []
    toc = []
    i = 0

    def flush_par(buf):
        if buf:
            out.append(Paragraph(inline(" ".join(buf)), S["body"]))
            out.append(Spacer(1, 1))
            buf.clear()

    buf = []
    while i < len(linhas):
        ln = linhas[i]
        s = ln.rstrip()

        # tabela
        if s.lstrip().startswith("|") and i + 1 < len(linhas) and \
           re.match(r"^\s*\|[\s:|-]+\|\s*$", linhas[i + 1]):
            flush_par(buf)
            rows = []
            hdr = s
            rows.append([c.strip() for c in hdr.strip().strip("|").split("|")])
            i += 2
            while i < len(linhas) and linhas[i].lstrip().startswith("|"):
                rows.append([c.strip() for c in linhas[i].strip().strip("|").split("|")])
                i += 1
            out.append(Spacer(1, 3))
            out.append(tabela(rows, header=True))
            out.append(Spacer(1, 8))
            continue

        # h1
        if s.startswith("# "):
            flush_par(buf)
            txt = s[2:].strip()
            out.append(SectionMark(txt))
            out.append(Paragraph(inline(txt), S["h1"]))
            out.append(Spacer(1, 5))
            i += 1
            continue

        # h2
        if s.startswith("## "):
            flush_par(buf)
            txt = s[3:].strip()
            m = re.match(r"^(\d+)\.\s*(.+)$", txt)
            if m:
                bloco = [Spacer(1, 6),
                         Paragraph("SEÇÃO " + m.group(1), S["h1num"])]
                titulo = m.group(2)
            else:
                bloco = []
                titulo = txt
            marca = SectionMark(txt)
            bloco.append(marca)
            bloco.append(Paragraph(inline(titulo), S["h2"]))
            bloco.append(HRule(color=colors.HexColor("#E3B778"), thick=1.1, pad=2))
            # mantem o cabecalho junto do paragrafo seguinte
            out.append(KeepTogether(bloco + [Spacer(1, 3)]))
            toc.append((txt, marca))
            i += 1
            continue

        # h3
        if s.startswith("### "):
            flush_par(buf)
            txt = s[4:].strip()
            out.append(Paragraph(inline(txt), S["h3"]))
            i += 1
            continue

        # hr
        if re.match(r"^\s*(---|\*\*\*|___)\s*$", s):
            flush_par(buf)
            out.append(Spacer(1, 4))
            out.append(HRule(color=BORDER, thick=0.6, pad=3))
            out.append(Spacer(1, 4))
            i += 1
            continue

        # blockquote -> callout
        if s.lstrip().startswith(">"):
            flush_par(buf)
            corpo = []
            while i < len(linhas) and linhas[i].lstrip().startswith(">"):
                corpo.append(linhas[i].lstrip()[1:].strip())
                i += 1
            txt = " ".join(x for x in corpo if x)
            bruto = " ".join(corpo)
            if "⚠" in bruto or "ATENÇÃO" in bruto.upper():
                tipo = "warn"
            elif "✓" in bruto:
                tipo = "ok"
            else:
                tipo = "info"
            out.append(Spacer(1, 2))
            out.append(callout(inline(txt), tipo))
            out.append(Spacer(1, 8))
            continue

        # checkbox
        m = re.match(r"^\s*[-*]\s+\[[ xX]\]\s+(.*)$", s)
        if m:
            flush_par(buf)
            out.append(Paragraph(inline(m.group(1)), S["bullet"], bulletText="☐"))
            i += 1
            continue

        # bullet
        m = re.match(r"^\s*[-*]\s+(.*)$", s)
        if m:
            flush_par(buf)
            txt = m.group(1)
            sub = re.match(r"^\*\*(.+?)\*\*\s*[—–-]\s*(.*)$", txt)
            out.append(Paragraph(inline(txt), S["bullet"], bulletText="•"))
            i += 1
            continue

        # numerada
        m = re.match(r"^\s*(\d+)\.\s+(.*)$", s)
        if m:
            flush_par(buf)
            out.append(Paragraph(inline(m.group(2)), S["num"],
                                 bulletText=m.group(1) + "."))
            i += 1
            continue

        # vazia
        if not s.strip():
            flush_par(buf)
            i += 1
            continue

        # paragrafo
        buf.append(s.strip())
        i += 1

    flush_par(buf)
    return out, toc


# ---------------------------------------------------------------- capa
def capa():
    f = []
    f.append(Spacer(1, 1.15 * cm))
    f.append(Paragraph(
        '<font color="#C2410C" size="9"><b>DOCUMENTO CONFIDENCIAL · PLANEJAMENTO</b></font>',
        ParagraphStyle("k", fontName="DJV-B", fontSize=9, leading=12)))
    f.append(Spacer(1, 0.35 * cm))

    f.append(Paragraph(
        '<font color="#0B1220" size="31"><b>Plano de Negócio</b></font>',
        ParagraphStyle("t1", fontName="DJV-B", fontSize=31, leading=35)))
    f.append(Spacer(1, 0.1 * cm))
    f.append(Paragraph(
        '<font color="#B45309" size="31"><b>Faucet de Criptomoedas</b></font>',
        ParagraphStyle("t2", fontName="DJV-B", fontSize=31, leading=35)))
    f.append(Spacer(1, 0.28 * cm))
    f.append(HRule(w=CONTENT_W, color=NAVY, thick=2.4, pad=0))
    f.append(Spacer(1, 0.42 * cm))

    f.append(Paragraph(
        "Análise de viabilidade, economia unitária, riscos legais e plano de 12 meses<br/>"
        "para um faucet operado por uma pessoa, a partir do Brasil.",
        ParagraphStyle("sub", fontName="DJV", fontSize=11.2, leading=17,
                       textColor=MUTED)))

    f.append(Spacer(1, 1.0 * cm))

    meta = [
        ["Preparado para", "Isaac (isaac-4000)"],
        ["Data de referência", "16 de outubro de 2026"],
        ["Base de cálculo", "BTC = US$ 82.000 · câmbio US$ 1 ≈ R$ 5,40"],
        ["Documento", "Plano de negócio · v1.0"],
    ]
    cells = [[Paragraph(f"<b>{a}</b>", S["cell"]), Paragraph(b, S["cell"])] for a, b in meta]
    t = Table(cells, colWidths=[4.3 * cm, CONTENT_W - 4.3 * cm])
    t.setStyle(TableStyle([
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 0),
        ("TOPPADDING", (0, 0), (-1, -1), 3),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 3),
        ("LINEBELOW", (0, 0), (-1, -3), 0.4, colors.HexColor("#E6EAF0")),
    ]))
    f.append(t)

    f.append(Spacer(1, 1.0 * cm))

    # bloco destaque
    bloco = [
        [Paragraph('<font color="#FFFFFF" size="8.5"><b>O NÚMERO QUE DEFINE TUDO</b></font>',
                   ParagraphStyle("x", fontName="DJV-B", fontSize=8.5, leading=11))],
        [Paragraph('<font color="#FFFFFF" size="25"><b>US$ 0,40</b></font>'
                   '<font color="#F5C577" size="10">  por usuário ativo, por mês</font>',
                   ParagraphStyle("y", fontName="DJV-B", fontSize=25, leading=29))],
        [Paragraph('<font color="#C9D6E4" size="8.6">'
                   'É quanto lucro líquido cada pessoa que volta ao site todos os dias gera. '
                   'Para faturar R$ 2.000/mês são necessários cerca de 1.000 usuários ativos diários.'
                   '</font>', ParagraphStyle("z", fontName="DJV", fontSize=8.6, leading=13))],
    ]
    tb = Table(bloco, colWidths=[CONTENT_W - 0.1 * cm])
    tb.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, -1), NAVY),
        ("LEFTPADDING", (0, 0), (-1, -1), 14),
        ("RIGHTPADDING", (0, 0), (-1, -1), 14),
        ("TOPPADDING", (0, 0), (0, 0), 11),
        ("BOTTOMPADDING", (0, -1), (-1, -1), 11),
        ("TOPPADDING", (0, 1), (-1, -1), 1),
        ("BOTTOMPADDING", (0, 0), (-1, 1), 1),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
    ]))
    f.append(tb)

    f.append(Spacer(1, 0.75 * cm))
    f.append(Paragraph(
        '<font color="#5A6B80" size="8">'
        'Este documento não substitui aconselhamento jurídico nem contábil. '
        'Números de mercado são referências datadas e devem ser reconferidos antes de '
        'qualquer decisão financeira.</font>',
        ParagraphStyle("disc", fontName="DJV", fontSize=8, leading=12)))
    return f


def sumario(paginas):
    f = []
    f.append(Paragraph("Sumário", S["h1"]))
    f.append(HRule(color=colors.HexColor("#E3B778"), thick=1.1, pad=2))
    f.append(Spacer(1, 9))

    linhas = []
    for idx, (titulo, pag) in enumerate(paginas, 1):
        num = re.match(r"^(\d+)\.\s*(.+)$", titulo)
        if num:
            n, nome = num.group(1), num.group(2)
        else:
            n, nome = str(idx), titulo
        pg = str(pag) if pag else ""
        linhas.append([
            Paragraph(f'<font color="#D97706"><b>{n}</b></font>', S["cell"]),
            Paragraph(inline(nome), S["cell"]),
            Paragraph(f'<font color="#5A6B80">{pg}</font>', S["cell"]),
        ])

    if linhas:
        t = Table(linhas, colWidths=[0.9 * cm, CONTENT_W - 2.5 * cm, 1.6 * cm])
        t.setStyle(TableStyle([
            ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
            ("ALIGN", (2, 0), (2, -1), "RIGHT"),
            ("LEFTPADDING", (0, 0), (-1, -1), 0),
            ("TOPPADDING", (0, 0), (-1, -1), 5.5),
            ("BOTTOMPADDING", (0, 0), (-1, -1), 5.5),
            ("LINEBELOW", (0, 0), (-1, -2), 0.4, colors.HexColor("#E6EAF0")),
        ]))
        f.append(t)
    return f


# ---------------------------------------------------------------- doc
class Doc(BaseDocTemplate):
    def __init__(self, fn, **kw):
        BaseDocTemplate.__init__(self, fn, pagesize=A4,
                                 leftMargin=M_L, rightMargin=M_R,
                                 topMargin=M_T, bottomMargin=M_B,
                                 title="Plano de Negócio — Faucet de Criptomoedas",
                                 author="Isaac (isaac-4000)",
                                 subject="Análise de viabilidade e plano de 12 meses")
        full = Frame(M_L, M_B, CONTENT_W, PAGE_H - M_T - M_B, id="full",
                     leftPadding=0, rightPadding=0, topPadding=0, bottomPadding=0)
        self.addPageTemplates([
            PageTemplate(id="cover", frames=[full], onPage=self._sem_rodape),
            PageTemplate(id="inner", frames=[full], onPage=self._rodape),
        ])

    def _sem_rodape(self, c, d):
        c.saveState()
        c.setFillColor(NAVY)
        c.rect(0, PAGE_H - 0.62 * cm, PAGE_W, 0.62 * cm, stroke=0, fill=1)
        c.setFillColor(GOLD)
        c.rect(0, PAGE_H - 0.70 * cm, PAGE_W, 0.08 * cm, stroke=0, fill=1)
        c.restoreState()

    def _rodape(self, c, d):
        c.saveState()
        # topo
        c.setFillColor(GOLD)
        c.rect(M_L, PAGE_H - M_T + 0.42 * cm, CONTENT_W, 0.055 * cm, stroke=0, fill=1)
        c.setFont("DJV", 7.2)
        c.setFillColor(MUTED)
        c.drawString(M_L, PAGE_H - M_T + 0.62 * cm,
                     "PLANO DE NEGÓCIO  ·  FAUCET DE CRIPTOMOEDAS")
        c.drawRightString(PAGE_W - M_R, PAGE_H - M_T + 0.62 * cm, "v1.0 · out/2026")
        # base
        c.setStrokeColor(BORDER)
        c.setLineWidth(0.5)
        c.line(M_L, M_B - 0.42 * cm, PAGE_W - M_R, M_B - 0.42 * cm)
        c.setFont("DJV", 7.6)
        c.setFillColor(MUTED)
        c.drawString(M_L, M_B - 0.75 * cm, "Isaac · isaac-4000")
        c.setFont("DJV-B", 8.2)
        c.setFillColor(NAVY)
        c.drawRightString(PAGE_W - M_R, M_B - 0.78 * cm, str(d.page))
        c.restoreState()


def build(md_path, out_path, toc_pages=None):
    md = open(md_path, encoding="utf-8").read()
    story = []
    story += capa()
    story += [NextPageTemplate("inner"), PageBreak()]
    story += sumario(toc_pages or [])
    story += [PageBreak()]

    corpo, toc = parse(md)
    story += corpo

    doc = Doc(out_path)
    doc.build(story)
    return toc


if __name__ == "__main__":
    MD = "../PLANO_DE_NEGOCIO_FAUCET.md"
    TMP = "/tmp/_pass1.pdf"
    OUT = "../Plano-de-Negocio-Faucet.pdf"

    # passada 1 — descobre as paginas
    toc1 = build(MD, TMP, toc_pages=[])
    paginas = [(t, None) for t, _ in toc1]

    # descobre numero de pagina lendo as marcas
    import pymupdf
    d = pymupdf.open(TMP)
    # fallback: recalcula por varredura de texto dos titulos
    titulos = [t for t, _ in toc1]
    for idx, tit in enumerate(titulos):
        alvo = limpar(re.sub(r"^\d+\.\s*", "", tit))[:38].lower()
        achado = None
        for pno in range(2, d.page_count):
            txt = re.sub(r"\s+", " ", d[pno].get_text()).lower()
            if alvo and alvo in txt:
                achado = pno + 1
                break
        paginas[idx] = (tit, achado)
    d.close()

    # passada 2 — com sumario preenchido
    build(MD, OUT, toc_pages=paginas)
    with open(TMP, "rb"):
        pass
    print("OK ->", OUT)

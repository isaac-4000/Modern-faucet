#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Desenha as telas do celular como imagem.

Por que este script existe:
    O preview ao vivo do sandbox fica atras de um portao que exige um token
    que nao existe dentro do sandbox. Quem acessa pelo navegador do celular
    nao ve o painel de preview. Entao as telas sao desenhadas a mao, com o
    texto e os numeros REAIS do codigo, para o usuario conseguir ver.

ATENCAO: isto e uma representacao fiel do layout e do conteudo, nao e uma
captura de tela do navegador. Os icones sao simplificados.
"""
import os

from PIL import Image, ImageDraw, ImageFont

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, "docs", "telas-celular")

FONT_DIR = "/usr/share/fonts/truetype/dejavu"

# ------------------------------------------------------------------ escala
S = 2                      # tudo em 2x para ficar nítido no celular
W, H = 390 * S, 844 * S

# ------------------------------------------------------------------- cores
BG_TOP = (239, 246, 255)   # blue-50
BG_BOT = (224, 231, 255)   # indigo-100
WHITE = (255, 255, 255)
BORDER = (229, 231, 235)   # gray-200
TXT = (31, 41, 55)         # gray-800
TXT_MED = (75, 85, 99)     # gray-600
TXT_SOFT = (107, 114, 128) # gray-500
TXT_FAINT = (156, 163, 175)
BLUE = (37, 99, 235)
BLUE_LIGHT = (219, 234, 254)
ORANGE = (249, 115, 22)
GREEN = (22, 163, 74)
GREEN_LIGHT = (240, 253, 244)
RED = (220, 38, 38)
GRAY_BAR = (229, 231, 235)


def f(name, size):
    # size e o tamanho LOGICO (como aparece numa tela de 390px).
    # Na tela 2x ele precisa do dobro, senao o texto sai pela metade.
    return ImageFont.truetype(os.path.join(FONT_DIR, name), size * S)


F_H1 = f("DejaVuSans-Bold.ttf", 22)
F_TITLE = f("DejaVuSans-Bold.ttf", 17)
F_BODY = f("DejaVuSans.ttf", 15)
F_BODY_B = f("DejaVuSans-Bold.ttf", 15)
F_SM = f("DejaVuSans.ttf", 13)
F_SM_B = f("DejaVuSans-Bold.ttf", 13)
F_XS = f("DejaVuSans.ttf", 11)
F_XS_B = f("DejaVuSans-Bold.ttf", 11)


def tw(d, txt, font):
    return d.textbbox((0, 0), txt, font=font)[2]


def th(d, txt, font):
    return d.textbbox((0, 0), txt, font=font)[3]


def wrap(d, txt, font, maxpx):
    """Quebra o texto em linhas que cabem em maxpx."""
    linhas, atual = [], ""
    for palavra in txt.split():
        teste = (atual + " " + palavra).strip()
        if tw(d, teste, font) <= maxpx or not atual:
            atual = teste
        else:
            linhas.append(atual)
            atual = palavra
    if atual:
        linhas.append(atual)
    return linhas


def texto(d, xy, txt, font, fill=TXT, ancora="la"):
    d.text(xy, txt, font=font, fill=fill, anchor=ancora)


def card(d, x, y, w, h, raio=12, fill=WHITE, borda=BORDER):
    d.rounded_rectangle([x, y, x + w, y + h], radius=raio * S // 2,
                        fill=fill, outline=borda, width=1 * S // 2)


def botao(d, x, y, w, h, rotulo, fill=BLUE, txt_fill=WHITE,
          fonte=F_BODY_B, raio=8, habilitado=True):
    cor = fill if habilitado else (156, 163, 175)
    d.rounded_rectangle([x, y, x + w, y + h], radius=raio * S // 2, fill=cor)
    d.text((x + w // 2, y + h // 2), rotulo, font=fonte, fill=txt_fill,
           anchor="mm")


def barra(d, x, y, w, h, pct, cor=BLUE):
    d.rounded_rectangle([x, y, x + w, y + h], radius=h // 2, fill=GRAY_BAR)
    if pct > 0:
        d.rounded_rectangle([x, y, x + int(w * pct / 100), y + h],
                            radius=h // 2, fill=cor)


# ------------------------------------------------------------- icones
def icone_casa(d, cx, cy, t, cor):
    d.polygon([(cx - t, cy), (cx, cy - t), (cx + t, cy)], fill=cor)
    d.rectangle([cx - t * 0.65, cy, cx + t * 0.65, cy + t], fill=cor)


def icone_moedas(d, cx, cy, t, cor):
    d.ellipse([cx - t, cy - t * 0.6, cx + t * 0.2, cy + t * 0.8],
              outline=cor, width=2 * S // 2)
    d.ellipse([cx - t * 0.3, cy - t, cx + t, cy + t * 0.4],
              outline=cor, width=2 * S // 2)


def icone_dado(d, cx, cy, t, cor):
    d.rounded_rectangle([cx - t, cy - t, cx + t, cy + t],
                        radius=t * 0.25, outline=cor, width=2 * S // 2)
    for dx, dy in [(-0.45, -0.45), (0.45, 0.45), (0, 0)]:
        r = t * 0.14
        d.ellipse([cx + dx * t - r, cy + dy * t - r,
                   cx + dx * t + r, cy + dy * t + r], fill=cor)


def icone_carteira(d, cx, cy, t, cor):
    d.rounded_rectangle([cx - t, cy - t * 0.7, cx + t, cy + t * 0.7],
                        radius=t * 0.2, outline=cor, width=2 * S // 2)
    d.rectangle([cx + t * 0.25, cy - t * 0.25, cx + t * 0.6, cy + t * 0.25],
                fill=cor)


def icone_gota(d, cx, cy, t, cor):
    d.polygon([(cx, cy - t), (cx + t * 0.75, cy + t * 0.3), (cx, cy + t),
               (cx - t * 0.75, cy + t * 0.3)], fill=cor)


ICONS = {"Início": icone_casa, "Faucet": icone_moedas,
         "Dados": icone_dado, "Sacar": icone_carteira}


# ------------------------------------------------------- partes fixas
def fundo(d, altura=H):
    for i in range(altura):
        p = i / H
        d.line([(0, i), (W, i)],
               fill=tuple(int(BG_TOP[c] + (BG_BOT[c] - BG_TOP[c]) * p)
                          for c in range(3)))


def cabecalho(d, saldo="742.50 pts"):
    d.rectangle([0, 0, W, 64 * S], fill=WHITE)
    d.line([(0, 64 * S), (W, 64 * S)], fill=BORDER, width=1 * S // 2)
    # logo
    d.ellipse([16 * S, 20 * S, 40 * S, 44 * S], fill=ORANGE)
    texto(d, (34 * S, 32 * S), "M", F_SM_B, WHITE, "mm")
    texto(d, (48 * S, 32 * S), "Modern Faucet", F_BODY_B, TXT, "lm")
    # saldo
    texto(d, (W - 96 * S, 32 * S), saldo, F_SM_B, TXT_MED, "rm")
    # sair
    d.rounded_rectangle([W - 86 * S, 20 * S, W - 16 * S, 44 * S],
                        radius=6 * S // 2, outline=BORDER, width=1 * S // 2)
    texto(d, (W - 51 * S, 32 * S), "Sair", F_XS, TXT_MED, "mm")


NAV_H = 56 * S


def menu_baixo(d, abas, ativo, altura=None):
    h = NAV_H
    topo = (altura or H) - h
    d.rectangle([0, topo, W, altura or H], fill=WHITE)
    d.line([(0, topo), (W, topo)], fill=BORDER, width=1 * S // 2)
    n = len(abas)
    for i, aba in enumerate(abas):
        cx = (i + 0.5) * (W / n)
        cor = BLUE if aba == ativo else TXT_FAINT
        ICONS[aba](d, cx, topo + 20 * S, 9 * S, cor)
        texto(d, (cx, topo + 40 * S), aba, F_XS, cor, "mm")


def barra_torneira(d, x, y, w):
    """A TankBar: título, valor, barra e rodapé."""
    icone_gota(d, x + 8 * S, y + 10 * S, 6 * S, BLUE)
    texto(d, (x + 20 * S, y + 10 * S), "Torneira de hoje", F_SM_B,
          TXT_MED, "lm")
    texto(d, (x + w, y + 10 * S), "R$ 7.58", F_SM_B, TXT_MED, "rm")
    largo = tw(d, "R$ 7.58", F_SM_B)
    texto(d, (x + w - largo - 4 * S, y + 10 * S), " de R$ 20.00",
          F_SM, TXT_MED, "rm")
    barra(d, x, y + 24 * S, w, 6 * S, 37.9)
    texto(d, (x, y + 40 * S), "37 saques pagos hoje · pague em DGB",
          F_XS, TXT_FAINT, "lm")
    return y + 58 * S


# ============================================ montagem (altura dinamica)
# A pagina do celular rola. Entao a gente desenha numa tela bem alta,
# acompanha ate onde o conteudo foi, e corta ali - senao o que sobra
# fica escondido atras do menu de baixo ou vazado fora da imagem.
ALTURA_MAX = 3000 * S


def montar(conteudo, abas, ativo):
    img = Image.new("RGB", (W, ALTURA_MAX), WHITE)
    d = ImageDraw.Draw(img)
    fundo(d, ALTURA_MAX)
    cabecalho(d)
    y = conteudo(d)
    fim = max(H, int(y) + 24 * S + NAV_H)
    out = img.crop((0, 0, W, fim))
    menu_baixo(ImageDraw.Draw(out), abas, ativo, fim)
    return out


# ====================================================== TELA 1: HOME
def conteudo_home(d):
    x = 16 * S
    w = W - 32 * S
    y = 96 * S

    titulo = "Ganhe cripto"
    largura_titulo = tw(d, titulo, F_H1)
    parte1_x = W // 2 - (largura_titulo + tw(d, " Grátis", F_H1)) // 2
    texto(d, (parte1_x, y), titulo, F_H1, TXT, "la")
    texto(d, (parte1_x + largura_titulo, y), " Grátis", F_H1, ORANGE, "la")
    y += 34 * S

    par = ("Uma faucet moderna e honesta. Você junta pontos a cada 4 "
           "minutos, troca por DigiByte (DGB) e multiplica no jogo de "
           "dados provably fair.")
    for linha in wrap(d, par, F_SM, w):
        texto(d, (W // 2, y), linha, F_SM, TXT_MED, "ma")
        y += 22 * S
    y += 18 * S

    card(d, x, y, w, 74 * S)
    barra_torneira(d, x + 16 * S, y + 12 * S, w - 32 * S)
    y += 92 * S

    recursos = [
        ("Faucet Gratuita", "Ganhe pontos grátis a cada 4 minutos. "
                            "Sem truques, sem pegadinhas."),
        ("Jogo de Dados", "Multiplique seus ganhos com nosso jogo de "
                          "dados provably fair."),
        ("Pagamentos Rápidos", "Saques pela FaucetPay, direto na sua "
                               "conta. Sem taxa escondida."),
    ]
    for titulo, desc in recursos:
        linhas = wrap(d, desc, F_SM, w - 76 * S)
        alt = 58 * S + len(linhas) * 20 * S + 14 * S
        card(d, x, y, w, alt)
        d.rounded_rectangle([x + 14 * S, y + 16 * S, x + 46 * S, y + 48 * S],
                            radius=6 * S // 2, fill=BLUE_LIGHT)
        texto(d, (x + 58 * S, y + 26 * S), titulo, F_BODY_B, TXT, "lm")
        for i, linha in enumerate(linhas):
            texto(d, (x + 14 * S, y + 60 * S + i * 20 * S), linha,
                  F_SM, TXT_MED, "lm")
        y += alt + 12 * S

    return y


# ==================================================== TELA 2: FAUCET
def conteudo_faucet(d):
    x = 16 * S
    w = W - 32 * S
    y = 88 * S

    d.ellipse([W // 2 - 24 * S, y, W // 2 + 24 * S, y + 48 * S],
              fill=(255, 237, 213))
    texto(d, (W // 2, y + 24 * S), "F", F_TITLE, ORANGE, "mm")
    y += 60 * S
    texto(d, (W // 2, y), "Faucet de Criptomoedas", F_TITLE, TXT, "ma")
    y += 26 * S
    texto(d, (W // 2, y), "Ganhe pontos a cada 4 minutos e troque por DGB",
          F_SM, TXT_MED, "ma")
    y += 26 * S

    card(d, x, y, w, 74 * S)
    barra_torneira(d, x + 16 * S, y + 12 * S, w - 32 * S)
    y += 92 * S

    cx = x + 16 * S
    cw = w - 32 * S

    # altura do card de claim
    card(d, x, y, w, 300 * S)
    cy = y + 16 * S

    icone_moedas(d, cx + 9 * S, cy + 9 * S, 8 * S, ORANGE)
    texto(d, (cx + 24 * S, cy + 9 * S), "Fazer Claim", F_BODY_B, TXT, "lm")
    cy += 30 * S
    texto(d, (cx, cy), "Ganhe 11 pontos grátis", F_SM, TXT_SOFT, "lm")
    cy += 28 * S

    # widget do captcha
    d.rounded_rectangle([cx, cy, cx + cw, cy + 66 * S],
                        radius=8, fill=(243, 244, 246),
                        outline=BORDER, width=1 * S // 2)
    d.rounded_rectangle([cx + 12 * S, cy + 14 * S, cx + 42 * S, cy + 44 * S],
                        radius=4, fill=WHITE, outline=(209, 213, 219),
                        width=1 * S // 2)
    d.ellipse([cx + 18 * S, cy + 22 * S, cx + 36 * S, cy + 36 * S],
              outline=(14, 165, 233), width=2)
    texto(d, (cx + 52 * S, cy + 20 * S), "Confirme que você é humano",
          F_SM, TXT_MED, "lm")
    texto(d, (cx + 52 * S, cy + 40 * S), "Cloudflare Turnstile",
          F_XS, TXT_FAINT, "lm")
    cy += 80 * S

    botao(d, cx, cy, cw, 48 * S, "Resgatar 11 pontos", ORANGE)
    cy += 64 * S

    d.line([(cx, cy), (cx + cw, cy)], fill=BORDER, width=1 * S // 2)
    cy += 18 * S
    texto(d, (cx, cy), "Seu saldo:", F_SM, TXT_MED, "lm")
    texto(d, (cx + cw, cy), "742.50 pontos", F_SM_B, ORANGE, "rm")
    cy += 28 * S
    d.rounded_rectangle([cx, cy, cx + cw, cy + 40 * S],
                        radius=6, outline=BORDER, width=1 * S // 2)
    icone_carteira(d, cx + 18 * S, cy + 20 * S, 7 * S, TXT_MED)
    texto(d, (cx + 34 * S, cy + 20 * S), "Sacar meus pontos", F_SM,
          TXT_MED, "lm")
    y += 318 * S

    stats = [("11", "Pontos por claim", ORANGE),
             ("4", "Minutos entre claims", BLUE),
             ("10%", "Comissão de referência", GREEN)]
    for valor, rotulo, cor in stats:
        card(d, x, y, w, 84 * S)
        texto(d, (x + 20 * S, y + 28 * S), valor, F_H1, cor, "lm")
        texto(d, (x + 20 * S, y + 58 * S), rotulo, F_SM, TXT_MED, "lm")
        y += 96 * S

    return y


# ===================================================== TELA 3: SACAR
def conteudo_sacar(d):
    x = 16 * S
    w = W - 32 * S
    y = 88 * S

    d.rounded_rectangle([W // 2 - 24 * S, y, W // 2 + 24 * S, y + 48 * S],
                        radius=24 * S, fill=(219, 234, 254))
    icone_carteira(d, W // 2, y + 24 * S, 11 * S, GREEN)
    y += 60 * S
    texto(d, (W // 2, y), "Sacar", F_TITLE, TXT, "ma")
    y += 24 * S
    texto(d, (W // 2, y),
          "Seus pontos viram DGB na sua conta da FaucetPay",
          F_XS, TXT_MED, "ma")
    y += 26 * S

    card(d, x, y, w, 74 * S)
    barra_torneira(d, x + 16 * S, y + 12 * S, w - 32 * S)
    y += 92 * S

    # ---- pedir saque
    cx = x + 16 * S
    cw = w - 32 * S
    card(d, x, y, w, 320 * S)
    cy = y + 16 * S

    texto(d, (cx, cy + 9 * S), "Pedir saque", F_BODY_B, TXT, "lm")
    cy += 30 * S
    texto(d, (cx, cy), "Mínimo de 500 pontos (≈ 1.43266476 DGB)",
          F_XS, TXT_SOFT, "lm")
    cy += 26 * S

    texto(d, (cx, cy), "Seu saldo", F_SM, TXT_MED, "lm")
    texto(d, (cx + cw, cy), "742.50 pontos", F_SM_B, GREEN, "rm")
    cy += 22 * S
    barra(d, cx, cy, cw, 6 * S, 100, GREEN)
    cy += 16 * S
    texto(d, (cx, cy), "Você já pode sacar.", F_XS, TXT_FAINT, "lm")
    cy += 26 * S

    d.line([(cx, cy), (cx + cw, cy)], fill=BORDER, width=1 * S // 2)
    cy += 18 * S
    texto(d, (cx, cy), "Carteira da FaucetPay", F_SM_B, TXT, "lm")
    cy += 24 * S
    d.rounded_rectangle([cx, cy, cx + cw, cy + 40 * S],
                        radius=6, fill=WHITE, outline=BORDER,
                        width=1 * S // 2)
    texto(d, (cx + 12 * S, cy + 20 * S), "e-mail, usuário ou endereço",
          F_SM, TXT_FAINT, "lm")
    cy += 48 * S
    texto(d, (cx, cy), "Ainda não tem conta? ", F_XS, TXT_FAINT, "lm")
    texto(d, (cx + tw(d, "Ainda não tem conta? ", F_XS), cy),
          "Crie uma grátis.", F_XS, BLUE, "lm")
    cy += 24 * S

    botao(d, cx, cy, cw, 48 * S, "Sacar agora", GREEN)
    y += 340 * S

    # ---- como funciona
    passos = [
        "1. Você junta pontos no faucet e no jogo de dados.",
        "2. Ao pedir o saque, os pontos viram DGB na cotação do",
        "   dia e vão para sua conta da FaucetPay.",
        "3. Da FaucetPay você saca para a carteira que quiser.",
    ]
    explica = wrap(d, "Pagamos até um valor fixo por dia, e a barra "
                      "acima mostra quanto ainda tem. Se acabar, é só "
                      "esperar o tanque encher de novo.", F_XS, w - 56 * S)
    alt = 44 * S + len(passos) * 20 * S + 16 * S + (18 * S + len(explica) * 17 * S) + 16 * S
    card(d, x, y, w, alt)
    cy = y + 16 * S
    texto(d, (x + 16 * S, cy), "Como funciona", F_BODY_B, TXT, "lm")
    cy += 32 * S
    for p in passos:
        texto(d, (x + 16 * S, cy), p, F_XS, TXT_MED, "lm")
        cy += 20 * S
    cy += 12 * S
    d.rounded_rectangle([x + 16 * S, cy, x + w - 16 * S,
                         cy + 22 * S + len(explica) * 17 * S],
                        radius=6, fill=(239, 246, 255))
    texto(d, (x + 28 * S, cy + 12 * S),
          "Por que existe um teto diário?", F_XS_B, (30, 58, 138), "lm")
    for i, linha in enumerate(explica):
        texto(d, (x + 28 * S, cy + 32 * S + i * 17 * S), linha,
              F_XS, (30, 64, 175), "lm")
    y += alt + 16 * S

    # ---- seus saques
    card(d, x, y, w, 160 * S)
    cy = y + 16 * S
    texto(d, (x + 16 * S, cy), "Seus saques", F_BODY_B, TXT, "lm")
    cy += 26 * S
    texto(d, (x + 16 * S, cy), "Últimos 50 pedidos", F_XS, TXT_SOFT, "lm")
    cy += 26 * S
    for rot, off in [("Quando", 0), ("Valor", 110),
                     ("Pontos", 205), ("Status", 268)]:
        texto(d, (x + 16 * S + off * S // 1, cy), rot, F_XS, TXT_FAINT, "lm")
    cy += 20 * S
    d.line([(x + 16 * S, cy), (x + w - 16 * S, cy)], fill=BORDER)
    cy += 12 * S
    for quando, valor, pontos, status in [
            ("19/10 09:47", "2.0415 DGB", "712.34", "Pago"),
            ("18/10 10:12", "1.6050 DGB", "560.00", "Pago")]:
        texto(d, (x + 16 * S, cy), quando, F_XS, TXT_MED, "lm")
        texto(d, (x + 16 * S + 110 * S, cy), valor, F_XS, TXT_MED, "lm")
        texto(d, (x + 16 * S + 205 * S, cy), pontos, F_XS, TXT_MED, "lm")
        texto(d, (x + 16 * S + 268 * S, cy), status, F_XS, GREEN, "lm")
        cy += 24 * S

    return y + 16 * S


# ============================================================ principal
def main():
    os.makedirs(OUT, exist_ok=True)
    telas = [("1-home.png", conteudo_home, "Início"),
             ("2-faucet.png", conteudo_faucet, "Faucet"),
             ("3-sacar.png", conteudo_sacar, "Sacar")]
    abas = ["Início", "Faucet", "Dados", "Sacar"]

    for nome, fn, ativo in telas:
        caminho = os.path.join(OUT, nome)
        montar(fn, abas, ativo).save(caminho)
        print("gerado:", caminho)

    # painel com as tres lado a lado
    imgs = [Image.open(os.path.join(OUT, n)) for n, _, _ in telas]
    miniatura = 800
    thumbs = [i.resize((int(miniatura * i.width / i.height), miniatura),
                       Image.LANCZOS) for i in imgs]
    gap = 24
    total_w = sum(t.width for t in thumbs) + gap * (len(thumbs) + 1)
    total_h = miniatura + gap * 2
    painel = Image.new("RGB", (total_w, total_h), (243, 244, 246))
    dx = gap
    for t in thumbs:
        painel.paste(t, (dx, gap))
        dx += t.width + gap
    caminho = os.path.join(OUT, "0-todas.png")
    painel.save(caminho)
    print("gerado:", caminho)


if __name__ == "__main__":
    main()

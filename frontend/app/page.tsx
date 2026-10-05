import Image from "next/image";
import Link from "next/link";
import { Atkinson_Hyperlegible, Crimson_Pro } from "next/font/google";
import {
  ArrowDown, ArrowRight, ArrowUpRight, Check, ClipboardList,
  FileCheck2, FileText, LockKeyhole, MessageSquareText, Search, ShieldCheck,
} from "lucide-react";
import styles from "./landing.module.css";

const bodyFont = Atkinson_Hyperlegible({
  subsets: ["latin"], weight: ["400", "700"], display: "swap",
  variable: "--font-landing-body",
});
const displayFont = Crimson_Pro({
  subsets: ["latin"], weight: ["400", "600", "700"], display: "swap",
  variable: "--font-landing-display",
});

const steps = [
  { number: "01", title: "Consulte antes de solicitar", description: "Encontre o serviço e confira as orientações e a documentação publicada.", icon: Search },
  { number: "02", title: "Envie o requerimento", description: "Descreva o pedido e inclua os documentos solicitados no formulário.", icon: FileText },
  { number: "03", title: "Acompanhe o atendimento", description: "Consulte o andamento e converse com a equipe no histórico do pedido.", icon: MessageSquareText },
];

const features = [
  { title: "Orientações antes do envio", description: "O catálogo público ajuda a identificar o serviço e o que precisa ser preparado.", icon: ClipboardList },
  { title: "Documentos ligados ao pedido", description: "Os arquivos enviados ficam associados ao requerimento correspondente.", icon: FileCheck2 },
  { title: "Histórico em um só lugar", description: "Mensagens e atualizações podem ser consultadas junto do atendimento.", icon: MessageSquareText },
  { title: "Acesso conforme o perfil", description: "Alunos e equipes visualizam as informações de acordo com suas permissões.", icon: ShieldCheck },
];

export default function LandingPage() {
  return (
    <div className={[styles.landing, bodyFont.variable, displayFont.variable].join(" ")}>
      <header className={styles.siteHeader}>
        <div className={styles.headerInner}>
          <Link href="/" className={styles.brand} aria-label="Chat Request, início">
            <span className={styles.brandSymbol} aria-hidden="true">CR</span>
            <span className={styles.brandText}><strong>Chat Request</strong><small>Atendimento acadêmico</small></span>
          </Link>
          <nav aria-label="Navegação principal" className={styles.primaryNav}>
            <a href="#como-funciona">Como funciona</a>
            <Link href="/servicos">Serviços</Link>
          </nav>
          <Link href="/login" className={styles.loginLink}>Entrar <ArrowUpRight size={18} aria-hidden="true" /></Link>
        </div>
      </header>

      <main id="conteudo-principal" tabIndex={-1}>
        <section className={styles.hero} aria-labelledby="hero-title">
          <div className={styles.heroInner}>
            <p className={styles.eyebrow}><span className={styles.eyebrowDot} aria-hidden="true" />Atendimento acadêmico digital</p>
            <h1 id="hero-title">Chat Request.<br />Seu requerimento tem <em>um caminho claro.</em></h1>
            <p className={styles.heroDescription}>Consulte os serviços, envie documentos e acompanhe as respostas em um único atendimento. Do primeiro passo ao retorno da equipe, tudo permanece junto do seu pedido.</p>
            <div className={styles.heroActions}>
              <Link href="/servicos" className={styles.primaryAction}>Conhecer os serviços <ArrowUpRight size={19} aria-hidden="true" /></Link>
              <a href="#como-funciona" className={styles.secondaryAction}>Entenda o processo <ArrowDown size={18} aria-hidden="true" /></a>
            </div>
            <p className={styles.accountNote}>Para abrir um pedido, entre com a conta fornecida pela instituição.</p>
          </div>

          <div className={styles.previewFrame} aria-label="Exemplo ilustrativo de um atendimento no Chat Request">
            <div className={styles.previewTopbar}>
              <div className={styles.previewIdentity}><span className={styles.previewLogo} aria-hidden="true">CR</span><span><strong>Chat Request</strong><small>Exemplo de acompanhamento</small></span></div>
              <span className={styles.previewPill}>Conteúdo ilustrativo</span>
            </div>
            <div className={styles.previewContent}>
              <div className={styles.previewSummary}>
                <p className={styles.previewOverline}>Requerimento acadêmico</p>
                <h2>Declaração de matrícula</h2>
                <p>Um lugar para consultar o pedido, os documentos e as respostas recebidas.</p>
                <div className={styles.previewStatus}><span className={styles.statusDot} aria-hidden="true" />Em análise</div>
                <div className={styles.previewDocument}><FileText size={20} aria-hidden="true" /><span>Documento anexado</span><Check size={18} aria-hidden="true" /></div>
              </div>
              <div className={styles.previewHistory}>
                <div className={styles.previewHistoryHeading}><span>Histórico do atendimento</span><MessageSquareText size={18} aria-hidden="true" /></div>
                <div className={styles.previewEvent}><span className={styles.previewEventIcon}><Check size={16} aria-hidden="true" /></span><div><strong>Pedido recebido</strong><p>Seu requerimento foi registrado.</p></div></div>
                <div className={styles.previewEvent}><span className={styles.previewEventIcon}><FileCheck2 size={16} aria-hidden="true" /></span><div><strong>Documentos no pedido</strong><p>Os anexos ficam disponíveis para análise.</p></div></div>
                <div className={styles.previewMessage}><Image src="/mascote.png" alt="" width={40} height={40} /><p><strong>Atendimento</strong><br />Acompanhe por aqui as mensagens e atualizações da equipe.</p></div>
              </div>
            </div>
          </div>
          <p className={styles.previewNote}>Uma visão ilustrativa do atendimento — sem dados reais de estudantes.</p>
        </section>

        <section id="como-funciona" className={styles.processSection} aria-labelledby="process-title">
          <div className={styles.sectionHeading}>
            <p className={styles.sectionEyebrow}>Do começo ao acompanhamento</p>
            <h2 id="process-title">Menos passos soltos. Mais clareza no atendimento.</h2>
            <p>O Chat Request reúne as ações essenciais do requerimento em uma sequência fácil de acompanhar.</p>
          </div>
          <ol className={styles.stepGrid}>
            {steps.map(({ number, title, description, icon: Icon }) => (
              <li key={number} className={styles.step}>
                <div className={styles.stepTop}><span className={styles.stepNumber}>{number}</span><Icon size={24} strokeWidth={1.8} aria-hidden="true" /></div>
                <h3>{title}</h3><p>{description}</p>
              </li>
            ))}
          </ol>
        </section>

        <section className={styles.featuresSection} aria-labelledby="features-title">
          <div className={styles.featuresInner}>
            <div className={styles.featuresIntro}>
              <p className={styles.sectionEyebrow}>O que fica no mesmo lugar</p>
              <h2 id="features-title">Um pedido com contexto, não uma conversa perdida.</h2>
              <p>Informação suficiente para enviar, acompanhar e compreender cada atendimento.</p>
              <Link href="/servicos" className={styles.inlineLink}>Explorar o catálogo <ArrowRight size={18} aria-hidden="true" /></Link>
            </div>
            <div className={styles.featureList}>
              {features.map(({ title, description, icon: Icon }) => (
                <article key={title} className={styles.feature}>
                  <span className={styles.featureIcon}><Icon size={23} strokeWidth={1.8} aria-hidden="true" /></span>
                  <div><h3>{title}</h3><p>{description}</p></div>
                </article>
              ))}
            </div>
          </div>
        </section>

        <section className={styles.rolesSection} aria-labelledby="roles-title">
          <div className={styles.sectionHeading}>
            <p className={styles.sectionEyebrow}>Uma plataforma, papéis distintos</p>
            <h2 id="roles-title">Cada pessoa vê o que precisa para seguir.</h2>
            <p>O atendimento é compartilhado, mas o acesso respeita a função de cada perfil.</p>
          </div>
          <div className={styles.roleGrid}>
            <article className={styles.roleCard}>
              <div className={styles.roleIcon}><FileText size={26} aria-hidden="true" /></div>
              <div><p className={styles.roleLabel}>Para estudantes</p><h3>Seu pedido sempre à vista.</h3></div>
              <p>Consulte serviços, envie seu requerimento e acompanhe as mensagens e atualizações do atendimento.</p>
              <span className={styles.roleTag}><LockKeyhole size={15} aria-hidden="true" /> Acesso com conta institucional</span>
            </article>
            <article className={[styles.roleCard, styles.roleCardTeam].join(" ")}>
              <div className={styles.roleIcon}><ShieldCheck size={26} aria-hidden="true" /></div>
              <div><p className={styles.roleLabel}>Para a equipe autorizada</p><h3>Atenda com o histórico em mãos.</h3></div>
              <p>Consulte os pedidos do seu escopo, analise documentos e responda com o contexto do atendimento.</p>
              <span className={styles.roleTag}><LockKeyhole size={15} aria-hidden="true" /> Permissões por perfil</span>
            </article>
          </div>
        </section>

        <section className={styles.ctaSection} aria-labelledby="cta-title">
          <div className={styles.ctaInner}>
            <div><p className={styles.ctaEyebrow}>Comece pelas orientações</p><h2 id="cta-title">O próximo passo do seu pedido começa aqui.</h2><p>Veja os serviços publicados e prepare as informações antes de entrar na plataforma.</p></div>
            <Link href="/servicos" className={styles.ctaAction}>Ver serviços <ArrowUpRight size={19} aria-hidden="true" /></Link>
          </div>
        </section>
      </main>

      <footer className={styles.footer}>
        <div className={styles.footerInner}>
          <div><p className={styles.footerBrand}>Chat Request</p><p className={styles.footerNote}>Plataforma em preparação para uso institucional.</p></div>
          <nav aria-label="Navegação do rodapé" className={styles.footerLinks}>
            <Link href="/servicos">Serviços</Link><a href="#como-funciona">Como funciona</a><Link href="/cradt-login">Acesso da equipe</Link>
          </nav>
        </div>
        <div className={styles.footerBottom}><span>© {new Date().getFullYear()} Chat Request</span><span>Atendimento acadêmico com informação no lugar certo.</span></div>
      </footer>
    </div>
  );
}

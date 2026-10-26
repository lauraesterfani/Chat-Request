"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

interface PublishedNotice {
  version: number;
  title: string;
  content: string;
  controller_name: string;
  contact_channel: string;
  legal_basis_summary: string;
  retention_summary: string;
  source_reference: string;
  institutional_approval_reference: string;
  effective_date: string;
  reviewed_at: string;
  published_at: string;
}

interface PublicNoticeResponse {
  status: "pending_approval" | "published";
  notice: PublishedNotice | null;
}

export default function PrivacyNoticePage() {
  const [result, setResult] = useState<PublicNoticeResponse | null>(null);
  const [loadError, setLoadError] = useState(false);

  useEffect(() => {
    let active = true;
    fetch("/api/privacy-notice/current", { headers: { Accept: "application/json" }, cache: "no-store" })
      .then(async (response) => {
        if (!response.ok) throw new Error("notice_unavailable");
        return await response.json() as PublicNoticeResponse;
      })
      .then((data) => { if (active) setResult(data); })
      .catch(() => { if (active) setLoadError(true); });
    return () => { active = false; };
  }, []);

  const publishedNotice = result?.status === "published" && result.notice ? result.notice : null;

  return (
    <main id="conteudo-principal" className="mx-auto max-w-4xl space-y-6 py-6 sm:py-10">
      <header className="space-y-3">
        <p className="text-xs font-bold uppercase tracking-[0.16em] text-emerald-800">Privacidade e dados pessoais</p>
        <h1 className="text-3xl font-bold text-slate-900 sm:text-4xl">Aviso de privacidade</h1>
        <p className="max-w-3xl text-base leading-7 text-slate-600">Consulte a versão institucional publicada para saber como os dados são tratados no Chat Request.</p>
      </header>

      {loadError && <section role="alert" className="rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950">
        <h2 className="font-bold">Aviso institucional ainda não disponível</h2>
        <p className="mt-2 text-sm leading-6">Não foi possível consultar uma versão aprovada. Nenhum conteúdo provisório é apresentado como aviso oficial. Tente novamente mais tarde.</p>
      </section>}

      {!result && !loadError && <p role="status" className="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600">Consultando a versão institucional…</p>}

      {result && !publishedNotice && <section aria-labelledby="pending-notice-title" className="rounded-2xl border border-amber-300 bg-amber-50 p-5 sm:p-7">
        <p className="text-xs font-bold uppercase tracking-[0.12em] text-amber-800">Estado atual</p>
        <h2 id="pending-notice-title" className="mt-2 text-xl font-bold text-amber-950 sm:text-2xl">Informação institucional pendente de aprovação</h2>
        <p className="mt-3 text-sm leading-6 text-amber-950 sm:text-base">
          O responsável institucional pelo tratamento, o canal oficial de privacidade, as bases legais e os prazos de retenção ainda precisam de confirmação e aprovação pelo IFPE. Por isso, este sistema não exibe um texto provisório como se fosse um aviso oficial.
        </p>
        <p className="mt-3 text-sm leading-6 text-amber-950">
          Você ainda pode abrir uma solicitação para análise humana. O registro no sistema não é uma confirmação de prazo legal, exportação de dados, alteração de registros históricos ou exclusão automática.
        </p>
        <Link href="/requests/privacidade" className="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-[#006b55] px-5 py-3 text-sm font-bold text-white hover:bg-[#005743] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800">Abrir uma solicitação sobre meus dados</Link>
      </section>}

      {publishedNotice && <article className="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        <header className="border-b border-slate-200 pb-4">
          <div className="flex flex-wrap items-start justify-between gap-3">
            <div><p className="text-xs font-bold uppercase tracking-[0.12em] text-emerald-800">Versão {publishedNotice.version}</p><h2 className="mt-2 text-2xl font-bold text-slate-900">{publishedNotice.title}</h2></div>
            <span className="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-900">Publicada</span>
          </div>
          <p className="mt-3 text-sm text-slate-600">Vigente desde {new Date(publishedNotice.effective_date).toLocaleDateString("pt-BR")} · Publicada em {new Date(publishedNotice.published_at).toLocaleDateString("pt-BR")}</p>
        </header>
        <section><h3 className="font-bold text-slate-900">Responsável institucional</h3><p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-slate-700">{publishedNotice.controller_name}</p></section>
        <section><h3 className="font-bold text-slate-900">Como entrar em contato</h3><p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-slate-700">{publishedNotice.contact_channel}</p></section>
        <section><h3 className="font-bold text-slate-900">Texto do aviso</h3><p className="mt-1 whitespace-pre-wrap text-sm leading-7 text-slate-700">{publishedNotice.content}</p></section>
        <section><h3 className="font-bold text-slate-900">Bases e finalidades informadas</h3><p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-slate-700">{publishedNotice.legal_basis_summary}</p></section>
        <section><h3 className="font-bold text-slate-900">Retenção</h3><p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-slate-700">{publishedNotice.retention_summary}</p></section>
        <footer className="border-t border-slate-200 pt-4 text-xs leading-5 text-slate-500">
          <p>Referência da publicação: {publishedNotice.source_reference}</p>
          <p className="mt-1">Referência da aprovação institucional: {publishedNotice.institutional_approval_reference}</p>
        </footer>
      </article>}

      <div className="flex flex-wrap gap-3">
        <Link href="/" className="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">Voltar ao início</Link>
        <Link href="/requests/privacidade" className="inline-flex min-h-11 items-center rounded-xl border border-emerald-700 px-4 text-sm font-semibold text-emerald-900 hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">Solicitações sobre meus dados</Link>
      </div>
    </main>
  );
}

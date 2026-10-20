"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { ArrowLeft, FilePenLine, Plus, Trash2 } from "lucide-react";

type Draft = { id: string; revision: number; updated_at: string; type?: { name?: string }; type_request_id: string; responses?: { subject?: unknown } };

export default function RequestDraftsPage() {
  const [drafts, setDrafts] = useState<Draft[]>([]);
  const [loading, setLoading] = useState(true);
  const [busyId, setBusyId] = useState<string | null>(null);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  const load = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const response = await fetch("/api/drafts", { headers: { Authorization: `Bearer ${sessionStorage.getItem("jwt_token") ?? ""}` } });
      if (!response.ok) throw new Error(response.status === 401 ? "Sua sessão expirou. Entre novamente para acessar os rascunhos." : "Não foi possível carregar seus rascunhos.");
      setDrafts(await response.json());
    } catch (caught) { setError(caught instanceof Error ? caught.message : "Não foi possível carregar seus rascunhos."); }
    finally { setLoading(false); }
  }, []);

  useEffect(() => { void load(); }, [load]);

  const discard = async (draft: Draft) => {
    if (!window.confirm(`Descartar o rascunho de ${draft.type?.name ?? "requerimento"}? Ele sairá da lista, mas a retenção dos dados segue a política institucional.`)) return;
    setBusyId(draft.id); setError(""); setNotice("");
    try {
      const response = await fetch(`/api/drafts/${draft.id}`, { method: "DELETE", headers: { Authorization: `Bearer ${sessionStorage.getItem("jwt_token") ?? ""}` } });
      if (!response.ok) throw new Error((await response.json()).message ?? "Não foi possível descartar este rascunho.");
      setDrafts((current) => current.filter((item) => item.id !== draft.id));
      setNotice("Rascunho descartado da sua lista.");
    } catch (caught) { setError(caught instanceof Error ? caught.message : "Não foi possível descartar este rascunho."); }
    finally { setBusyId(null); }
  };

  return <div className="mx-auto max-w-3xl space-y-6 py-5 sm:py-8">
    <header className="flex flex-wrap items-start gap-4">
      <Link href="/requests/new" aria-label="Voltar ao novo requerimento" className="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700"><ArrowLeft size={18} aria-hidden="true" /></Link>
      <div className="min-w-0 flex-1"><p className="text-xs font-bold uppercase tracking-wider text-emerald-800">Solicitações acadêmicas</p><h1 className="mt-1 text-2xl font-bold text-slate-900">Meus rascunhos</h1><p className="mt-1 text-sm text-slate-700">Continue um pedido não enviado ou descarte um rascunho que não será utilizado.</p></div>
    </header>
    {error && <div role="alert" className="rounded-xl border border-red-300 bg-red-50 p-4 text-sm font-medium text-red-900">{error} <button type="button" onClick={() => void load()} className="ml-2 underline">Tentar novamente</button></div>}
    {notice && <div role="status" className="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm font-medium text-emerald-950">{notice}</div>}
    {loading ? <p role="status" className="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-700">Carregando rascunhos…</p> : drafts.length === 0 ? <section className="rounded-2xl border border-slate-200 bg-white p-8 text-center"><FilePenLine aria-hidden="true" size={32} className="mx-auto text-emerald-800" /><h2 className="mt-3 text-lg font-bold text-slate-900">Nenhum rascunho em aberto</h2><p className="mt-1 text-sm text-slate-700">Quando você começar um requerimento, ele aparecerá aqui após o salvamento.</p><Link href="/requests/new" className="mt-5 inline-flex min-h-11 items-center gap-2 rounded-lg bg-dark-green px-4 text-sm font-semibold text-white"><Plus size={17} aria-hidden="true" />Novo requerimento</Link></section> : <ul className="space-y-3">{drafts.map((draft) => <li key={draft.id} className="flex flex-wrap items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 sm:p-5"><FilePenLine aria-hidden="true" size={22} className="shrink-0 text-emerald-800" /><div className="min-w-0 flex-1"><h2 className="font-bold text-slate-900">{draft.type?.name ?? "Requerimento"}</h2><p className="mt-1 break-words text-sm text-slate-800"><span className="font-semibold">Assunto:</span> {typeof draft.responses?.subject === "string" && draft.responses.subject.trim() ? draft.responses.subject : "Ainda não preenchido"}</p><p className="mt-1 text-sm text-slate-600">Salvo em {new Intl.DateTimeFormat("pt-BR", { dateStyle: "short", timeStyle: "short" }).format(new Date(draft.updated_at))} · revisão {draft.revision}</p></div><div className="flex w-full gap-2 sm:w-auto"><Link href={`/requests/new?draft=${encodeURIComponent(draft.id)}`} className="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg bg-dark-green px-4 text-sm font-semibold text-white sm:flex-none">Continuar</Link><button type="button" onClick={() => void discard(draft)} disabled={busyId === draft.id} aria-label={`Descartar rascunho de ${draft.type?.name ?? "requerimento"}`} className="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-red-200 text-red-800 hover:bg-red-50 disabled:opacity-50"><Trash2 size={18} aria-hidden="true" /></button></div></li>)}</ul>}
  </div>;
}

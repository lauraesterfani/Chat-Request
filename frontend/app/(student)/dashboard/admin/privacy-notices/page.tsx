"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";

type NoticeStatus = "draft" | "review" | "published";
interface PrivacyNotice {
  id: string;
  version: number;
  title: string;
  content: string;
  controller_name: string | null;
  contact_channel: string | null;
  legal_basis_summary: string | null;
  retention_summary: string | null;
  source_reference: string | null;
  institutional_approval_reference: string | null;
  effective_date: string | null;
  status: NoticeStatus;
  author_id: string;
  reviewer_id: string | null;
  review_submitted_at: string | null;
  reviewed_at: string | null;
  published_at: string | null;
  author?: { id: string; name: string };
  reviewer?: { id: string; name: string } | null;
}
interface PageResult { data: PrivacyNotice[]; total: number }
interface NoticeForm {
  title: string;
  content: string;
  controller_name: string;
  contact_channel: string;
  legal_basis_summary: string;
  retention_summary: string;
  source_reference: string;
  institutional_approval_reference: string;
  effective_date: string;
}

const emptyForm: NoticeForm = {
  title: "",
  content: "",
  controller_name: "",
  contact_channel: "",
  legal_basis_summary: "",
  retention_summary: "",
  source_reference: "",
  institutional_approval_reference: "",
  effective_date: "",
};
const statusLabels: Record<NoticeStatus, string> = { draft: "Rascunho", review: "Em revisão", published: "Publicada" };

async function getError(response: Response): Promise<string> {
  const data = await response.json().catch(() => ({}));
  const first = data.errors ? Object.values(data.errors).flat()[0] : null;
  return first ?? data.message ?? "Não foi possível concluir a operação.";
}

function formFromNotice(notice: PrivacyNotice): NoticeForm {
  return {
    title: notice.title,
    content: notice.content,
    controller_name: notice.controller_name ?? "",
    contact_channel: notice.contact_channel ?? "",
    legal_basis_summary: notice.legal_basis_summary ?? "",
    retention_summary: notice.retention_summary ?? "",
    source_reference: notice.source_reference ?? "",
    institutional_approval_reference: notice.institutional_approval_reference ?? "",
    effective_date: notice.effective_date?.slice(0, 10) ?? "",
  };
}

export default function PrivacyNoticesAdminPage() {
  const router = useRouter();
  const [items, setItems] = useState<PrivacyNotice[]>([]);
  const [selected, setSelected] = useState<PrivacyNotice | null>(null);
  const [form, setForm] = useState<NoticeForm>(emptyForm);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  const loadItems = useCallback(async () => {
    setLoading(true);
    setError("");
    const token = sessionStorage.getItem("jwt_token");
    if (!token) { router.replace("/cradt-login"); return; }
    try {
      const response = await fetch("/api/admin/privacy-notices?per_page=100", {
        headers: { Accept: "application/json", Authorization: "Bearer " + token }, cache: "no-store",
      });
      if (!response.ok) throw new Error(await getError(response));
      const result = await response.json() as PageResult;
      setItems(result.data);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : "Não foi possível carregar as versões.");
    } finally { setLoading(false); }
  }, [router]);

  useEffect(() => { void loadItems(); }, [loadItems]);

  function selectNotice(item: PrivacyNotice) {
    setSelected(item);
    setForm(formFromNotice(item));
    setError("");
    setNotice("");
  }

  function startDraft() {
    setSelected(null);
    setForm(emptyForm);
    setError("");
    setNotice("");
  }

  function payload(): NoticeForm {
    return { ...form, title: form.title.trim(), content: form.content.trim() };
  }

  async function saveDraft(event?: FormEvent<HTMLFormElement>) {
    event?.preventDefault();
    if (saving || (selected && selected.status !== "draft")) return;
    const token = sessionStorage.getItem("jwt_token");
    if (!token) { router.replace("/cradt-login"); return; }
    setSaving(true); setError(""); setNotice("");
    try {
      const response = await fetch(selected ? "/api/admin/privacy-notices/" + selected.id : "/api/admin/privacy-notices", {
        method: selected ? "PUT" : "POST",
        headers: { Accept: "application/json", Authorization: "Bearer " + token, "Content-Type": "application/json" },
        body: JSON.stringify(payload()),
      });
      if (!response.ok) throw new Error(await getError(response));
      const saved = await response.json() as PrivacyNotice;
      setSelected(saved); setForm(formFromNotice(saved));
      setNotice("Rascunho salvo. Ele não está disponível ao público.");
      await loadItems();
    } catch (saveError) {
      setError(saveError instanceof Error ? saveError.message : "Não foi possível salvar o rascunho.");
    } finally { setSaving(false); }
  }

  async function perform(action: "submit-review" | "return-to-draft" | "publish" | "new-version") {
    if (!selected || saving) return;
    const token = sessionStorage.getItem("jwt_token");
    if (!token) { router.replace("/cradt-login"); return; }
    setSaving(true); setError(""); setNotice("");
    try {
      const response = await fetch("/api/admin/privacy-notices/" + selected.id + "/" + action, {
        method: "POST",
        headers: { Accept: "application/json", Authorization: "Bearer " + token, "Content-Type": "application/json" },
        body: action === "publish" ? JSON.stringify(payload()) : "{}",
      });
      if (!response.ok) throw new Error(await getError(response));
      const updated = await response.json() as PrivacyNotice;
      setNotice(action === "new-version" ? "Nova versão criada como rascunho." : action === "publish" ? "Versão publicada com as referências institucionais registradas." : action === "submit-review" ? "Versão enviada para revisão." : "Versão devolvida para rascunho.");
      await loadItems();
      setSelected(updated); setForm(formFromNotice(updated));
    } catch (actionError) {
      setError(actionError instanceof Error ? actionError.message : "Não foi possível avançar a versão.");
    } finally { setSaving(false); }
  }

  const isEditable = !selected || selected.status === "draft";
  const canCompleteInstitutionalFields = isEditable || selected?.status === "review";

  return (
    <main className="mx-auto max-w-7xl space-y-6 py-4 sm:py-8">
      <header className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p className="text-xs font-bold uppercase tracking-[0.16em] text-emerald-800">Privacidade · gestão de versões</p>
          <h1 className="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">Avisos de privacidade</h1>
          <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Prepare rascunhos e registre a revisão. A publicação exige fonte e referência de aprovação institucional. A função de administrador no sistema não designa a pessoa como encarregada de dados do IFPE.</p>
        </div>
        <Link href="/dashboard/admin" className="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">Voltar ao painel</Link>
      </header>

      <div className="grid gap-5 lg:grid-cols-[minmax(0,0.82fr)_minmax(0,1.18fr)]">
        <section aria-labelledby="notice-versions-heading" className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <h2 id="notice-versions-heading" className="text-lg font-bold text-slate-900">Versões registradas</h2>
            <button type="button" onClick={startDraft} className="inline-flex min-h-11 items-center rounded-xl bg-[#006b55] px-4 text-sm font-bold text-white hover:bg-[#005743] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800">Novo rascunho</button>
          </div>
          <p className="mt-2 text-xs leading-5 text-slate-600">Rascunhos e versões em revisão são restritos à equipe autorizada e nunca são expostos pela página pública.</p>
          {error && <p role="alert" className="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}
          {loading ? <p role="status" className="mt-5 text-sm text-slate-600">Carregando versões…</p> : items.length === 0 ? <p className="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">Nenhuma versão foi criada. O aviso público permanece pendente de aprovação.</p> : (
            <ul className="mt-4 space-y-2">
              {items.map((item) => <li key={item.id}>
                <button type="button" onClick={() => selectNotice(item)} aria-pressed={selected?.id === item.id} className={"w-full rounded-xl border p-4 text-left transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700 " + (selected?.id === item.id ? "border-emerald-600 bg-emerald-50" : "border-slate-200 hover:border-emerald-300 hover:bg-slate-50")}>
                  <span className="flex flex-wrap items-start justify-between gap-2"><span className="font-semibold text-slate-900">v{item.version} · {item.title || "Sem título"}</span><span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{statusLabels[item.status]}</span></span>
                  <span className="mt-2 block text-xs text-slate-500">Autoria: {item.author?.name ?? "Equipe"}{item.reviewed_at ? " · Revisada em " + new Date(item.reviewed_at).toLocaleDateString("pt-BR") : ""}{item.published_at ? " · Publicada em " + new Date(item.published_at).toLocaleDateString("pt-BR") : ""}</span>
                </button>
              </li>)}
            </ul>
          )}
        </section>

        <section aria-labelledby="notice-editor-heading" className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
          <div className="flex flex-wrap items-start justify-between gap-3">
            <div><h2 id="notice-editor-heading" className="text-lg font-bold text-slate-900">{selected ? "Versão " + selected.version : "Novo rascunho"}</h2><p className="mt-1 text-sm text-slate-600">{selected ? statusLabels[selected.status] : "Ainda não salvo"}{selected?.author?.name ? " · criado por " + selected.author.name : ""}</p></div>
            {selected?.status === "published" && <button type="button" disabled={saving} onClick={() => void perform("new-version")} className="min-h-11 rounded-xl border border-emerald-700 px-3 text-sm font-semibold text-emerald-900 hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700 disabled:opacity-60">Criar nova versão</button>}
          </div>

          <div className="mt-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm leading-6 text-amber-950">A publicação só fica disponível após fonte e referência de aprovação institucional, responsável pelo tratamento, canal de contato, bases/finalidades, retenção e data de vigência. Nenhum dado institucional presumido é preenchido automaticamente.</div>

          {notice && <p role="status" className="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{notice}</p>}
          {error && <p role="alert" className="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}

          <form onSubmit={(event) => void saveDraft(event)} className="mt-5 space-y-4">
            <div><label htmlFor="privacy-notice-title" className="block text-sm font-semibold text-slate-800">Título</label><input id="privacy-notice-title" value={form.title} onChange={(event) => setForm({ ...form, title: event.target.value })} maxLength={180} required disabled={!isEditable} className="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700 disabled:bg-slate-50" /></div>
            <div><label htmlFor="privacy-notice-content" className="block text-sm font-semibold text-slate-800">Texto do aviso</label><textarea id="privacy-notice-content" value={form.content} onChange={(event) => setForm({ ...form, content: event.target.value })} minLength={20} maxLength={20000} rows={7} required disabled={!isEditable} className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm leading-6 text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700 disabled:bg-slate-50" placeholder="Não inclua conteúdo oficial até que seja validado institucionalmente." /></div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div><label htmlFor="privacy-controller" className="block text-sm font-semibold text-slate-800">Responsável institucional pelo tratamento</label><input id="privacy-controller" value={form.controller_name} onChange={(event) => setForm({ ...form, controller_name: event.target.value })} maxLength={255} disabled={!canCompleteInstitutionalFields} className="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700 disabled:bg-slate-50" /></div>
              <div><label htmlFor="privacy-contact" className="block text-sm font-semibold text-slate-800">Canal oficial de contato</label><input id="privacy-contact" value={form.contact_channel} onChange={(event) => setForm({ ...form, contact_channel: event.target.value })} maxLength={255} disabled={!canCompleteInstitutionalFields} className="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700 disabled:bg-slate-50" /></div>
              <div><label htmlFor="privacy-legal-bases" className="block text-sm font-semibold text-slate-800">Bases legais e finalidades</label><textarea id="privacy-legal-bases" value={form.legal_basis_summary} onChange={(event) => setForm({ ...form, legal_basis_summary: event.target.value })} maxLength={4000} rows={3} disabled={!canCompleteInstitutionalFields} className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm leading-6 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700 disabled:bg-slate-50" /></div>
              <div><label htmlFor="privacy-retention" className="block text-sm font-semibold text-slate-800">Retenção e descarte</label><textarea id="privacy-retention" value={form.retention_summary} onChange={(event) => setForm({ ...form, retention_summary: event.target.value })} maxLength={4000} rows={3} disabled={!canCompleteInstitutionalFields} className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm leading-6 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700 disabled:bg-slate-50" /></div>
              <div><label htmlFor="privacy-source-reference" className="block text-sm font-semibold text-slate-800">Fonte institucional</label><input id="privacy-source-reference" value={form.source_reference} onChange={(event) => setForm({ ...form, source_reference: event.target.value })} maxLength={500} disabled={!canCompleteInstitutionalFields} className="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700 disabled:bg-slate-50" placeholder="Documento, resolução ou endereço institucional" /></div>
              <div><label htmlFor="privacy-approval-reference" className="block text-sm font-semibold text-slate-800">Referência de aprovação institucional</label><input id="privacy-approval-reference" value={form.institutional_approval_reference} onChange={(event) => setForm({ ...form, institutional_approval_reference: event.target.value })} maxLength={500} disabled={!canCompleteInstitutionalFields} className="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700 disabled:bg-slate-50" placeholder="Identificação verificável da aprovação" /></div>
              <div><label htmlFor="privacy-effective-date" className="block text-sm font-semibold text-slate-800">Data de vigência</label><input id="privacy-effective-date" type="date" value={form.effective_date} onChange={(event) => setForm({ ...form, effective_date: event.target.value })} disabled={!canCompleteInstitutionalFields} className="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700 disabled:bg-slate-50" /></div>
              {selected?.reviewer && <div className="self-center text-sm text-slate-600">Revisada por {selected.reviewer.name}{selected.reviewed_at ? " em " + new Date(selected.reviewed_at).toLocaleString("pt-BR") : ""}</div>}
            </div>

            {isEditable && <div className="flex flex-wrap gap-3 border-t border-slate-200 pt-4">
              <button type="submit" disabled={saving} className="inline-flex min-h-11 items-center rounded-xl bg-[#006b55] px-5 text-sm font-bold text-white hover:bg-[#005743] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 disabled:bg-slate-400">{saving ? "Salvando…" : selected ? "Salvar rascunho" : "Criar rascunho"}</button>
              {selected?.status === "draft" && <button type="button" disabled={saving} onClick={() => void perform("submit-review")} className="inline-flex min-h-11 items-center rounded-xl border border-emerald-700 px-4 text-sm font-semibold text-emerald-900 hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700 disabled:opacity-60">Enviar para revisão</button>}
            </div>}
          </form>

          {selected?.status === "review" && <div className="mt-5 flex flex-wrap gap-3 border-t border-slate-200 pt-4">
            <button type="button" disabled={saving} onClick={() => void perform("return-to-draft")} className="inline-flex min-h-11 items-center rounded-xl border border-slate-400 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700 disabled:opacity-60">Devolver para edição</button>
            <button type="button" disabled={saving} onClick={() => void perform("publish")} className="inline-flex min-h-11 items-center rounded-xl bg-[#006b55] px-5 text-sm font-bold text-white hover:bg-[#005743] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 disabled:bg-slate-400">{saving ? "Publicando…" : "Publicar versão aprovada"}</button>
          </div>}
          {selected?.status === "published" && <p className="mt-5 rounded-xl bg-emerald-50 p-4 text-sm leading-6 text-emerald-950">Esta versão é imutável. Para uma atualização, crie uma nova versão e registre nova revisão, fonte, aprovação e data de vigência.</p>}
        </section>
      </div>
    </main>
  );
}

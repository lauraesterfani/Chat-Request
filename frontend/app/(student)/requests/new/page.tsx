"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { AlertCircle, Paperclip, Save, X } from "lucide-react";

type RequestType = { id: string; name: string; requires_document: boolean; document_instructions?: string };
type Field = { key: string; label: string; type: string; required?: boolean; instruction?: string; options?: string[] };
type Draft = { id: string; revision: number; type_request_id: string; form_schema_version_id?: string | null; responses?: Record<string, unknown> };

export default function NewRequestPage() {
  const router = useRouter();
  const [types, setTypes] = useState<RequestType[]>([]);
  const [typeId, setTypeId] = useState("");
  const [fields, setFields] = useState<Field[]>([]);
  const [versionId, setVersionId] = useState<string | null>(null);
  const [answers, setAnswers] = useState<Record<string, unknown>>({ subject: "", description: "" });
  const [draft, setDraft] = useState<Draft | null>(null);
  const [saveState, setSaveState] = useState(" ");
  const [file, setFile] = useState<File | null>(null);
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  const token = () => sessionStorage.getItem("jwt_token") ?? "";
  const selected = useMemo(() => types.find((item) => item.id === typeId), [types, typeId]);
  const subject = String(answers.subject ?? "");
  const description = String(answers.description ?? "");

  useEffect(() => {
    fetch("/api/type-requests", { headers: { Authorization: `Bearer ${token()}` } }).then(async (response) => response.ok && setTypes(await response.json()));
    fetch("/api/drafts", { headers: { Authorization: `Bearer ${token()}` } }).then(async (response) => {
      if (!response.ok) return;
      const drafts: Draft[] = await response.json();
      if (drafts[0]) { setDraft(drafts[0]); setTypeId(drafts[0].type_request_id); setAnswers(drafts[0].responses ?? {}); }
    });
  }, []);

  useEffect(() => {
    if (!typeId) return;
    fetch(`/api/type-requests/${typeId}/form`, { headers: { Authorization: `Bearer ${token()}` } }).then(async (response) => {
      if (!response.ok) return;
      const data = await response.json(); setFields(data.schema?.fields ?? []); setVersionId(data.version_id ?? null);
    });
  }, [typeId]);

  useEffect(() => {
    if (!typeId || !Object.keys(answers).length) return;
    setSaveState("Salvando rascunho…");
    const timeout = window.setTimeout(async () => {
      const response = await fetch(draft ? `/api/drafts/${draft.id}` : "/api/drafts", { method: draft ? "PUT" : "POST", headers: { Authorization: `Bearer ${token()}`, "Content-Type": "application/json" }, body: JSON.stringify({ type_request_id: typeId, form_schema_version_id: versionId, responses: answers, revision: draft?.revision }) });
      if (response.ok) { const saved = await response.json(); setDraft(saved); setSaveState("Rascunho salvo"); } else if (response.status === 409) setSaveState("Conflito: recarregue antes de continuar."); else setSaveState("Não foi possível salvar o rascunho.");
    }, 900);
    return () => window.clearTimeout(timeout);
  }, [answers, typeId, versionId]);

  const setAnswer = (key: string, value: unknown) => setAnswers((current) => ({ ...current, [key]: value }));
  const submit = async (event: React.FormEvent) => {
    event.preventDefault(); setError("");
    if (selected?.requires_document && !file) { setError(`Anexe: ${selected.document_instructions ?? "documento comprobatório"}.`); return; }
    setSubmitting(true);
    try {
      const documentIds: string[] = [];
      if (file) { const upload = new FormData(); upload.append("arquivo", file); const response = await fetch("/api/documents/upload", { method: "POST", headers: { Authorization: `Bearer ${token()}` }, body: upload }); const data = await response.json(); if (!response.ok) throw new Error(data.message); documentIds.push(data.id); }
      const key = crypto.randomUUID();
      const response = await fetch("/api/requests", { method: "POST", headers: { Authorization: `Bearer ${token()}`, "Content-Type": "application/json", "Idempotency-Key": key }, body: JSON.stringify({ type_id: typeId, subject, description, form_schema_version_id: versionId, form_responses: answers, document_ids: documentIds, draft_id: draft?.id, idempotency_key: key }) });
      const data = await response.json(); if (!response.ok) throw new Error(data.message); router.push(`/requests/visualizar/${data.id}`);
    } catch (caught) { setError(caught instanceof Error ? caught.message : "Não foi possível enviar o requerimento."); } finally { setSubmitting(false); }
  };

  return <main className="mx-auto max-w-2xl py-8"><section className="overflow-hidden rounded-2xl border bg-white shadow-sm"><header className="bg-[#108542] px-6 py-5 text-white"><h1 className="text-2xl font-bold">Novo requerimento</h1><p className="text-sm text-green-100">Os dados são salvos como rascunho no servidor.</p></header><form onSubmit={submit} className="space-y-5 p-6">{error && <p role="alert" className="flex gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-700"><AlertCircle size={18} />{error}</p>}<label className="block text-sm font-semibold">Tipo de solicitação<select required value={typeId} onChange={(event) => { setTypeId(event.target.value); setAnswers({ subject: "", description: "" }); }} className="mt-1 w-full rounded-lg border p-3"><option value="">Selecione…</option>{types.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select></label>{fields.map((field) => <label key={field.key} className="block text-sm font-semibold">{field.label}{field.required && " *"}{field.instruction && <span className="ml-2 font-normal text-slate-500">{field.instruction}</span>}{field.type === "long_text" ? <textarea required={field.required} value={String(answers[field.key] ?? "")} onChange={(event) => setAnswer(field.key, event.target.value)} className="mt-1 min-h-28 w-full rounded-lg border p-3" /> : field.type === "boolean" ? <input aria-label={field.label} type="checkbox" checked={Boolean(answers[field.key])} onChange={(event) => setAnswer(field.key, event.target.checked)} className="ml-3" /> : field.type === "single_select" ? <select required={field.required} value={String(answers[field.key] ?? "")} onChange={(event) => setAnswer(field.key, event.target.value)} className="mt-1 w-full rounded-lg border p-3"><option value="">Selecione…</option>{field.options?.map((option) => <option key={option}>{option}</option>)}</select> : <input required={field.required} type={field.type === "number" ? "number" : field.type === "date" ? "date" : "text"} value={String(answers[field.key] ?? "")} onChange={(event) => setAnswer(field.key, event.target.value)} className="mt-1 w-full rounded-lg border p-3" />}</label>)}{selected?.requires_document && <label className="block rounded-xl border border-dashed border-amber-400 bg-amber-50 p-4 text-sm font-semibold"><span className="flex items-center gap-2"><Paperclip size={17} />Anexo obrigatório</span><span className="mt-1 block text-xs font-normal">{selected.document_instructions}</span><input type="file" onChange={(event) => setFile(event.target.files?.[0] ?? null)} className="mt-3 block w-full text-sm" /></label>}<p aria-live="polite" className="flex items-center gap-2 text-xs text-slate-500"><Save size={14} />{saveState}</p><div className="flex gap-3"><button type="button" onClick={() => router.push("/me")} className="flex-1 rounded-xl bg-slate-100 py-3 font-bold"><X size={17} className="mr-1 inline" />Cancelar</button><button disabled={submitting} className="flex-[2] rounded-xl bg-[#108542] py-3 font-bold text-white disabled:opacity-50">{submitting ? "Enviando…" : "Revisar e enviar"}</button></div></form></section></main>;
}

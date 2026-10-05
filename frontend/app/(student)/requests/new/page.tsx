"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { AlertCircle, Paperclip, Save, X } from "lucide-react";

type RequestType = { id: string; name: string; requires_document: boolean; document_instructions?: string };
type Condition = { field: string; operator: "equals" | "in" | "present"; value?: unknown };
type Field = { key: string; label: string; type: string; required?: boolean; instruction?: string; options?: string[]; min?: number; max?: number; max_length?: number; max_items?: number; visible_if?: { all?: Condition[]; any?: Condition[] } };
type Draft = { id: string; revision: number; type_request_id: string; form_schema_version_id?: string | null; responses?: Record<string, unknown> };
type DraftSnapshot = { type_request_id: string; form_schema_version_id: string | null; responses: Record<string, unknown> };
const baseFields: Field[] = [
  { key: "subject", label: "Assunto", type: "short_text", required: true, max_length: 255 },
  { key: "description", label: "Descrição", type: "long_text", required: true, max_length: 10000 },
];

function isVisible(field: Field, answers: Record<string, unknown>): boolean {
  if (!field.visible_if) return true;
  const rules = field.visible_if.all ?? field.visible_if.any ?? [];
  const matches = rules.map((rule) => {
    const value = answers[rule.field];
    if (rule.operator === "present") return value !== undefined && value !== null && value !== "" && !(Array.isArray(value) && value.length === 0);
    if (rule.operator === "in") return Array.isArray(rule.value) && rule.value.includes(value);
    return value === rule.value;
  });
  return field.visible_if.all ? matches.every(Boolean) : matches.some(Boolean);
}

function displayAnswer(value: unknown): string {
  if (typeof value === "boolean") return value ? "Sim" : "Não";
  if (Array.isArray(value)) return value.map((item) => typeof item === "object" && item !== null ? String((item as { subject?: string }).subject ?? "") : String(item)).join(", ");
  return String(value ?? "Não informado");
}

export default function NewRequestPage() {
  const router = useRouter();
  const [types, setTypes] = useState<RequestType[]>([]);
  const [typeId, setTypeId] = useState("");
  const [fields, setFields] = useState<Field[]>([]);
  const [versionId, setVersionId] = useState<string | null>(null);
  const [answers, setAnswers] = useState<Record<string, unknown>>({ subject: "", description: "" });
  const [draft, setDraft] = useState<Draft | null>(null);
  const draftRef = useRef<Draft | null>(null);
  const saveQueue = useRef<Promise<Draft | null>>(Promise.resolve(null));
  const editSequence = useRef(0);
  const editCount = useRef(0);
  const savedEditCount = useRef(0);
  const savedSnapshot = useRef<string | null>(null);
  const activeType = useRef("");
  const submitted = useRef(false);
  const uploadedFile = useRef<{ file: File; id: string } | null>(null);
  const submissionAttempt = useRef<{ payload: string; file: File | null; draft: Draft; documentIds: string[] } | null>(null);
  const [conflictDraft, setConflictDraft] = useState<Draft | null>(null);
  const [undoAnswers, setUndoAnswers] = useState<Record<string, unknown> | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [reviewing, setReviewing] = useState(false);
  const errorRef = useRef<HTMLDivElement>(null);
  const [saveState, setSaveState] = useState(" ");
  const [file, setFile] = useState<File | null>(null);
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  const token = () => sessionStorage.getItem("jwt_token") ?? "";
  const selected = useMemo(() => types.find((item) => item.id === typeId), [types, typeId]);
  const subject = String(answers.subject ?? "");
  const description = String(answers.description ?? "");
  const displayFields = typeId ? [...baseFields.filter((base) => !fields.some((field) => field.key === base.key)), ...fields] : [];
  const visibleFields = displayFields.filter((field) => isVisible(field, answers));

  useEffect(() => { if (error) errorRef.current?.focus(); }, [error]);

  useEffect(() => {
    fetch("/api/type-requests", { headers: { Authorization: `Bearer ${token()}` } }).then(async (response) => response.ok && setTypes(await response.json()));
    fetch("/api/drafts", { headers: { Authorization: `Bearer ${token()}` } }).then(async (response) => {
      if (!response.ok) return;
      const drafts: Draft[] = await response.json();
      if (drafts[0]) {
        activeType.current = drafts[0].type_request_id;
        draftRef.current = drafts[0];
        savedSnapshot.current = JSON.stringify([drafts[0].type_request_id, drafts[0].form_schema_version_id ?? null, drafts[0].responses ?? {}]);
        setDraft(drafts[0]); setTypeId(drafts[0].type_request_id); setVersionId(drafts[0].form_schema_version_id ?? null); setAnswers(drafts[0].responses ?? {});
      }
    });
  }, []);

  useEffect(() => {
    if (!typeId) return;
    const controller = new AbortController();
    fetch(`/api/type-requests/${typeId}/form`, { headers: { Authorization: `Bearer ${token()}` }, signal: controller.signal }).then(async (response) => {
      if (!response.ok) { setError("Não foi possível carregar o formulário deste serviço."); return; }
      const data = await response.json();
      if (!controller.signal.aborted && activeType.current === typeId) { setFields(data.schema?.fields ?? []); setVersionId(data.version_id ?? null); }
    }).catch(() => { if (!controller.signal.aborted) setError("Não foi possível carregar o formulário deste serviço."); });
    return () => controller.abort();
  }, [typeId]);

  const persistDraft = useCallback(async (snapshot: DraftSnapshot): Promise<Draft | null> => {
    if (submitted.current || activeType.current !== snapshot.type_request_id) return null;
    const current = draftRef.current?.type_request_id === snapshot.type_request_id ? draftRef.current : null;
    const signature = JSON.stringify([snapshot.type_request_id, snapshot.form_schema_version_id, snapshot.responses]);
    if (current && signature === savedSnapshot.current) return current;
    const savedThroughEdit = editCount.current;
    try {
      const response = await fetch(current ? `/api/drafts/${current.id}` : "/api/drafts", {
        method: current ? "PUT" : "POST",
        headers: { Authorization: `Bearer ${token()}`, "Content-Type": "application/json" },
        body: JSON.stringify({ ...snapshot, revision: current?.revision }),
      });
      const data = await response.json();
      if (response.status === 409) {
        if (data.draft) setConflictDraft(data.draft);
        setSaveState("Há alterações em outra aba. Suas respostas nesta aba foram preservadas.");
        return null;
      }
      if (!response.ok) {
        setSaveState(data.message ?? "Não foi possível salvar o rascunho.");
        return null;
      }
      if (activeType.current !== snapshot.type_request_id) return null;
      draftRef.current = data;
      savedSnapshot.current = signature;
      savedEditCount.current = Math.max(savedEditCount.current, savedThroughEdit);
      setDraft(data);
      setSaveState("Rascunho salvo");
      return data;
    } catch {
      setSaveState("Sem conexão. Suas respostas continuam nesta tela; tente novamente.");
      return null;
    }
  }, []);

  useEffect(() => {
    if (!typeId || !Object.keys(answers).length || conflictDraft || submitting || submitted.current || editCount.current === savedEditCount.current) return;
    const sequence = ++editSequence.current;
    const snapshot = { type_request_id: typeId, form_schema_version_id: versionId, responses: answers };
    setSaveState("Salvando rascunho…");
    const timeout = window.setTimeout(() => {
      saveQueue.current = saveQueue.current.then(() => sequence === editSequence.current ? persistDraft(snapshot) : null);
    }, 900);
    return () => window.clearTimeout(timeout);
  }, [answers, typeId, versionId, conflictDraft, submitting, persistDraft]);

  const setAnswer = (key: string, value: unknown) => {
    editCount.current += 1;
    const next = { ...answers, [key]: value };
    const removed = fields.filter((field) => !isVisible(field, next) && next[field.key] !== undefined && next[field.key] !== "" && next[field.key] !== null && (!Array.isArray(next[field.key]) || (next[field.key] as unknown[]).length > 0));
    if (removed.length) {
      setUndoAnswers(answers);
      removed.forEach((field) => { delete next[field.key]; });
    } else {
      setUndoAnswers(null);
    }
    setFieldErrors({});
    setAnswers(next);
  };

  const changeType = async (nextTypeId: string) => {
    if (nextTypeId === typeId) return;
    if (typeId && !conflictDraft) {
      await saveQueue.current;
      const saved = await persistDraft({ type_request_id: typeId, form_schema_version_id: versionId, responses: answers });
      if (!saved) { setError("Salve ou resolva o rascunho atual antes de trocar de serviço."); return; }
    }
    activeType.current = nextTypeId;
    submitted.current = false;
    draftRef.current = null;
    savedSnapshot.current = null;
    editCount.current = 0;
    savedEditCount.current = 0;
    uploadedFile.current = null;
    submissionAttempt.current = null;
    setDraft(null);
    setTypeId(nextTypeId);
    setVersionId(null);
    setFields([]);
    setAnswers({ subject: "", description: "" });
    setFile(null);
    setConflictDraft(null);
    setUndoAnswers(null);
    setFieldErrors({});
    setReviewing(false);
    setError("");
  };

  const reviewRequest = (event: React.FormEvent) => {
    event.preventDefault();
    setError("");
    if (selected?.requires_document && !file) { setError(`Anexe: ${selected.document_instructions ?? "documento comprobatório"}.`); return; }
    if (conflictDraft) { setError("Resolva o conflito do rascunho antes de enviar."); return; }
    setReviewing(true);
  };
  const submit = async (event: React.FormEvent) => {
    event.preventDefault(); setError("");
    if (conflictDraft) { setError("Resolva o conflito do rascunho antes de enviar."); return; }
    if (selected?.requires_document && !file) { setError(`Anexe: ${selected.document_instructions ?? "documento comprobatório"}.`); return; }
    setSubmitting(true);
    try {
      const formResponses = Object.fromEntries(fields.filter((field) => Object.prototype.hasOwnProperty.call(answers, field.key)).map((field) => [field.key, answers[field.key]]));
      const payload = JSON.stringify([typeId, versionId, answers]);
      const replay = submissionAttempt.current?.payload === payload && submissionAttempt.current.file === file ? submissionAttempt.current : null;
      let saved: Draft;
      let documentIds: string[];
      if (replay) {
        saved = replay.draft;
        documentIds = replay.documentIds;
      } else {
        await saveQueue.current;
        const latestDraft = await persistDraft({ type_request_id: typeId, form_schema_version_id: versionId, responses: answers });
        if (!latestDraft) throw new Error("Não foi possível salvar o rascunho. Revise o aviso acima e tente novamente.");
        saved = latestDraft;
        documentIds = [];
        if (file) {
          if (uploadedFile.current?.file === file) {
            documentIds.push(uploadedFile.current.id);
          } else {
            const upload = new FormData(); upload.append("arquivo", file);
            const response = await fetch("/api/documents/upload", { method: "POST", headers: { Authorization: `Bearer ${token()}` }, body: upload });
            const data = await response.json(); if (!response.ok) throw new Error(data.message);
            uploadedFile.current = { file, id: data.id }; documentIds.push(data.id);
          }
        }
        submissionAttempt.current = { payload, file, draft: saved, documentIds };
      }
      const key = `draft:${saved.id}`;
      const response = await fetch("/api/requests", { method: "POST", headers: { Authorization: `Bearer ${token()}`, "Content-Type": "application/json", "Idempotency-Key": key }, body: JSON.stringify({ type_id: typeId, subject, description, form_schema_version_id: versionId, form_responses: formResponses, document_ids: documentIds, draft_id: saved.id, idempotency_key: key }) });
      const data = await response.json();
      if (!response.ok) {
        if (data.errors) { setFieldErrors(data.errors); setReviewing(false); }
        throw new Error(data.message ?? "Não foi possível enviar o requerimento.");
      }
      submitted.current = true;
      router.push(`/requests/visualizar/${data.id}`);
    } catch (caught) { setError(caught instanceof Error ? caught.message : "Não foi possível enviar o requerimento."); } finally { setSubmitting(false); }
  };

  return (
    <div className="mx-auto max-w-2xl py-6 sm:py-8">
      <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header className="bg-dark-green px-5 py-6 text-white sm:px-8">
          <p className="text-sm font-medium text-emerald-100">Solicitações acadêmicas</p>
          <h1 className="mt-1 text-2xl font-bold">Novo requerimento</h1>
          <p className="mt-2 text-sm text-emerald-50">{reviewing ? "Confira os dados antes de confirmar o envio." : "Preencha com calma; suas respostas ficam salvas como rascunho."}</p>
        </header>
        <form onSubmit={reviewing ? submit : reviewRequest} className="space-y-6 p-5 sm:p-8">
          {error && <div ref={errorRef} tabIndex={-1} role="alert" className="flex gap-2 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800"><AlertCircle aria-hidden="true" size={18} className="shrink-0" /><span>{error}</span></div>}
          {conflictDraft && !reviewing && (
            <div role="alert" className="space-y-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
              <p className="font-semibold">Este rascunho também foi alterado em outra aba. O texto desta aba não foi apagado.</p>
              <div className="flex flex-wrap gap-2">
                <button type="button" onClick={() => { draftRef.current = conflictDraft; savedSnapshot.current = JSON.stringify([conflictDraft.type_request_id, conflictDraft.form_schema_version_id ?? null, conflictDraft.responses ?? {}]); savedEditCount.current = editCount.current; setDraft(conflictDraft); setAnswers(conflictDraft.responses ?? {}); setConflictDraft(null); }} className="min-h-11 rounded-lg border border-amber-600 px-3 font-semibold">Usar versão salva</button>
                <button type="button" disabled={conflictDraft.type_request_id !== typeId || conflictDraft.form_schema_version_id !== versionId} onClick={() => { draftRef.current = conflictDraft; savedSnapshot.current = JSON.stringify([conflictDraft.type_request_id, conflictDraft.form_schema_version_id ?? null, conflictDraft.responses ?? {}]); editCount.current += 1; setDraft(conflictDraft); setConflictDraft(null); setAnswers({ ...answers }); }} className="min-h-11 rounded-lg bg-dark-green px-3 font-semibold text-white disabled:opacity-50">Salvar meu texto nesta aba</button>
              </div>
            </div>
          )}
          {undoAnswers && !reviewing && <div className="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-100 p-3 text-sm"><span>Uma resposta ficou oculta e foi removida ao mudar a opção.</span><button type="button" onClick={() => { editCount.current += 1; setAnswers(undoAnswers); setUndoAnswers(null); }} className="min-h-11 font-semibold text-dark-green underline">Desfazer</button></div>}

          {reviewing ? (
            <section aria-labelledby="review-title" className="space-y-4">
              <h2 id="review-title" className="text-lg font-bold text-dark-green">Revise seu requerimento</h2>
              <p className="text-sm text-slate-600">O protocolo será gerado somente depois da confirmação.</p>
              <dl className="divide-y divide-slate-200 rounded-xl border border-slate-200 px-4">
                <div className="py-3"><dt className="text-sm font-semibold text-slate-600">Serviço</dt><dd className="mt-1 break-words">{selected?.name}</dd></div>
                {visibleFields.map((field) => <div key={field.key} className="py-3"><dt className="text-sm font-semibold text-slate-600">{field.label}</dt><dd className="mt-1 whitespace-pre-wrap break-words">{displayAnswer(answers[field.key])}</dd></div>)}
                {file && <div className="py-3"><dt className="text-sm font-semibold text-slate-600">Documento</dt><dd className="mt-1 break-all">{file.name}</dd></div>}
              </dl>
              <div className="flex flex-col-reverse gap-3 sm:flex-row">
                <button type="button" onClick={() => setReviewing(false)} className="min-h-12 flex-1 rounded-xl border border-slate-300 px-4 font-semibold">Voltar e editar</button>
                <button disabled={submitting} className="min-h-12 flex-[2] rounded-xl bg-dark-green px-4 font-bold text-white disabled:opacity-50">{submitting ? "Enviando…" : "Confirmar envio"}</button>
              </div>
            </section>
          ) : (
            <>
              <div>
                <label htmlFor="request-type" className="block text-sm font-semibold">Tipo de solicitação <span aria-hidden="true">*</span></label>
                <select id="request-type" required value={typeId} onChange={(event) => void changeType(event.target.value)} className="mt-2 min-h-12 w-full rounded-lg border border-slate-300 bg-white p-3"><option value="">Selecione…</option>{types.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select>
              </div>
              {visibleFields.map((field) => {
                const inputId = `form-${field.key}`;
                const fieldError = fieldErrors[`form_responses.${field.key}`]?.[0];
                const descriptionId = fieldError ? `${inputId}-error` : field.instruction ? `${inputId}-hint` : undefined;
                const inputClass = "mt-2 min-h-12 w-full rounded-lg border border-slate-300 bg-white p-3 text-base";
                const items = Array.isArray(answers[field.key]) ? answers[field.key] as Array<{ subject: string; description?: string }> : [];
                return <div key={field.key}>
                  {field.type === "subjects" || field.type === "multi_select"
                    ? <p id={`${inputId}-label`} className="text-sm font-semibold">{field.label}{field.required && <span aria-hidden="true"> *</span>}</p>
                    : <label htmlFor={inputId} className="block text-sm font-semibold">{field.label}{field.required && <span aria-hidden="true"> *</span>}</label>}
                  {field.instruction && <p id={`${inputId}-hint`} className="mt-1 text-sm text-slate-600">{field.instruction}</p>}
                  {field.type === "long_text" ? <textarea id={inputId} required={field.required} maxLength={field.max_length ?? 10000} value={String(answers[field.key] ?? "")} onChange={(event) => setAnswer(field.key, event.target.value)} aria-invalid={Boolean(fieldError)} aria-describedby={descriptionId} className={`${inputClass} min-h-32`} />
                    : field.type === "boolean" ? <select id={inputId} required={field.required} value={answers[field.key] === true ? "true" : answers[field.key] === false ? "false" : ""} onChange={(event) => setAnswer(field.key, event.target.value === "" ? null : event.target.value === "true")} aria-invalid={Boolean(fieldError)} aria-describedby={descriptionId} className={inputClass}><option value="">Selecione…</option><option value="true">Sim</option><option value="false">Não</option></select>
                      : field.type === "single_select" ? <select id={inputId} required={field.required} value={String(answers[field.key] ?? "")} onChange={(event) => setAnswer(field.key, event.target.value)} aria-invalid={Boolean(fieldError)} aria-describedby={descriptionId} className={inputClass}><option value="">Selecione…</option>{field.options?.map((option) => <option key={option} value={option}>{option}</option>)}</select>
                        : field.type === "multi_select" ? <div id={inputId} role="group" aria-labelledby={`${inputId}-label`} aria-describedby={descriptionId} className="mt-2 grid gap-2">{field.options?.map((option) => <label key={option} className="flex min-h-11 items-center gap-3 rounded-lg border border-slate-200 px-3"><input type="checkbox" checked={(Array.isArray(answers[field.key]) ? answers[field.key] as string[] : []).includes(option)} onChange={(event) => { const values = Array.isArray(answers[field.key]) ? answers[field.key] as string[] : []; setAnswer(field.key, event.target.checked ? [...values, option] : values.filter((value) => value !== option)); }} className="h-5 w-5 accent-emerald-700" />{option}</label>)}</div>
                          : field.type === "subjects" ? <div id={inputId} role="group" aria-labelledby={`${inputId}-label`} aria-describedby={descriptionId} className="mt-2 space-y-3">{items.map((item, index) => <div key={index} className="rounded-lg border border-slate-200 p-3"><label className="block text-sm">Assunto {index + 1}<input value={item.subject} onChange={(event) => setAnswer(field.key, items.map((entry, itemIndex) => itemIndex === index ? { ...entry, subject: event.target.value } : entry))} className={inputClass} /></label><label className="mt-2 block text-sm">Descrição<textarea value={item.description ?? ""} onChange={(event) => setAnswer(field.key, items.map((entry, itemIndex) => itemIndex === index ? { ...entry, description: event.target.value } : entry))} className={`${inputClass} min-h-24`} /></label><button type="button" onClick={() => setAnswer(field.key, items.filter((_, itemIndex) => itemIndex !== index))} className="mt-2 min-h-11 text-sm font-semibold text-red-700">Remover assunto</button></div>)}<button type="button" disabled={items.length >= (field.max_items ?? 20)} onClick={() => setAnswer(field.key, [...items, { subject: "", description: "" }])} className="min-h-11 rounded-lg border border-emerald-700 px-3 font-semibold text-dark-green disabled:opacity-50">Adicionar assunto</button></div>
                            : <input id={inputId} required={field.required} type={field.type === "number" ? "number" : field.type === "date" ? "date" : "text"} min={field.min} max={field.max} maxLength={field.type === "short_text" ? field.max_length ?? 255 : undefined} value={String(answers[field.key] ?? "")} onChange={(event) => setAnswer(field.key, field.type === "number" ? event.target.value === "" ? null : Number(event.target.value) : event.target.value)} aria-invalid={Boolean(fieldError)} aria-describedby={descriptionId} className={inputClass} />}
                  {fieldError && <p id={`${inputId}-error`} role="alert" className="mt-2 text-sm font-medium text-red-700">{fieldError}</p>}
                </div>;
              })}
              {selected?.requires_document && <label className="block rounded-xl border border-dashed border-amber-400 bg-amber-50 p-4 text-sm font-semibold"><span className="flex items-center gap-2"><Paperclip aria-hidden="true" size={17} />Anexo obrigatório</span><span className="mt-1 block text-sm font-normal">{selected.document_instructions}</span><input type="file" accept=".pdf,.jpg,.jpeg,.png" onChange={(event) => { uploadedFile.current = null; submissionAttempt.current = null; setFile(event.target.files?.[0] ?? null); }} className="mt-3 block w-full text-sm" /></label>}
              <p aria-live="polite" className="flex items-center gap-2 text-sm text-slate-600"><Save aria-hidden="true" size={16} />{saveState}{draft && <span className="sr-only">Revisão {draft.revision}</span>}</p>
              <div className="flex flex-col-reverse gap-3 sm:flex-row"><button type="button" onClick={() => router.push("/me")} className="min-h-12 flex-1 rounded-xl bg-slate-100 px-4 font-bold"><X aria-hidden="true" size={17} className="mr-1 inline" />Cancelar</button><button disabled={submitting || Boolean(conflictDraft)} className="min-h-12 flex-[2] rounded-xl bg-dark-green px-4 font-bold text-white disabled:opacity-50">Revisar requerimento</button></div>
            </>
          )}
        </form>
      </section>
    </div>
  );
}

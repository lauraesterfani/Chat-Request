"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { ArrowLeft, ArrowDown, ArrowUp, Eye, Plus, Save, Send, Trash2 } from "lucide-react";

type FieldType = "short_text" | "long_text" | "number" | "date" | "single_select" | "multi_select" | "boolean" | "subjects";
type ConditionValue = string | number | boolean;
type Condition = { field: string; operator: "equals" | "in" | "present"; value?: ConditionValue | ConditionValue[] };
type Field = { key: string; label: string; type: FieldType; required?: boolean; instruction?: string; options?: string[]; min?: number; max?: number; max_length?: number; max_items?: number; visible_if?: { all?: Condition[]; any?: Condition[] } };
type Version = { id: string; version: number; status: "draft" | "published" | "superseded"; schema: { fields: Field[] }; change_summary: string | null; created_at: string; published_at: string | null };
type RequestType = { id: string; name: string };

const fieldTypes: { value: FieldType; label: string }[] = [
  { value: "short_text", label: "Texto curto" }, { value: "long_text", label: "Texto longo" },
  { value: "number", label: "Número" }, { value: "date", label: "Data" },
  { value: "single_select", label: "Seleção única" }, { value: "multi_select", label: "Seleção múltipla" },
  { value: "boolean", label: "Sim ou não" }, { value: "subjects", label: "Disciplinas" },
];
const inputClass = "min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-offset-2";
const secondaryButton = "inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50";
const primaryButton = "inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-dark-green px-4 text-sm font-semibold text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50";

function visible(field: Field, answers: Record<string, unknown>): boolean {
  const condition = field.visible_if;
  if (!condition) return true;
  const rules = condition.all ?? condition.any ?? [];
  const match = (rule: Condition) => {
    const answer = answers[rule.field];
    if (rule.operator === "present") return answer !== undefined && answer !== null && answer !== "" && (!Array.isArray(answer) || answer.length > 0);
    if (rule.operator === "in") return Array.isArray(rule.value) && rule.value.includes(answer as ConditionValue);
    return answer === rule.value;
  };
  return condition.all ? rules.every(match) : rules.some(match);
}

function conditionValue(raw: string, source?: Field): ConditionValue {
  if (source?.type === "boolean") return raw === "true";
  if (source?.type === "number" && raw.trim() !== "") return Number(raw);
  return raw;
}

function parseApiError(data: { message?: string; errors?: Record<string, string[]> }): string {
  const first = data.errors && Object.values(data.errors)[0]?.[0];
  return first ?? data.message ?? "Não foi possível concluir a operação.";
}

export default function FormEditorPage() {
  const { typeId } = useParams<{ typeId: string }>();
  const [typeName, setTypeName] = useState("Formulário");
  const [versions, setVersions] = useState<Version[]>([]);
  const [draftId, setDraftId] = useState<string | null>(null);
  const [viewingVersionId, setViewingVersionId] = useState<string | null>(null);
  const [fields, setFields] = useState<Field[]>([]);
  const [summary, setSummary] = useState("");
  const [answers, setAnswers] = useState<Record<string, unknown>>({});
  const [busy, setBusy] = useState(false);
  const [loading, setLoading] = useState(true);
  const [dirty, setDirty] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const errorRef = useRef<HTMLDivElement>(null);

  const published = versions.find((version) => version.status === "published");
  const readOnly = Boolean(viewingVersionId && versions.find((version) => version.id === viewingVersionId)?.status !== "draft");
  const comparison = useMemo(() => {
    const old = new Map((published?.schema.fields ?? []).map((field) => [field.key, field]));
    const next = new Map(fields.map((field) => [field.key, field]));
    return {
      added: fields.filter((field) => !old.has(field.key)).map((field) => field.label || field.key),
      removed: [...old.values()].filter((field) => !next.has(field.key)).map((field) => field.label),
      changed: fields.filter((field) => old.has(field.key) && JSON.stringify(old.get(field.key)) !== JSON.stringify(field)).map((field) => field.label || field.key),
    };
  }, [fields, published]);

  const load = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const token = sessionStorage.getItem("jwt_token");
      const headers = { Authorization: `Bearer ${token ?? ""}` };
      const [typesResponse, versionsResponse] = await Promise.all([
        fetch("/api/type-requests", { headers }),
        fetch(`/api/type-requests/${typeId}/form-versions`, { headers }),
      ]);
      if (!versionsResponse.ok) throw new Error(versionsResponse.status === 403 || versionsResponse.status === 401 ? "Seu perfil não pode configurar formulários." : "Não foi possível carregar as versões.");
      const typeData: RequestType[] = typesResponse.ok ? await typesResponse.json() : [];
      const versionData: Version[] = await versionsResponse.json();
      setTypeName(typeData.find((type) => type.id === typeId)?.name ?? "Formulário");
      setVersions(versionData);
      const current = versionData.find((version) => version.status === "draft") ?? versionData.find((version) => version.status === "published");
      setViewingVersionId(current?.id ?? null);
      setDraftId(current?.status === "draft" ? current.id : null);
      setFields(current?.schema.fields ?? []);
      setSummary(current?.status === "draft" ? current.change_summary ?? "" : "");
      setDirty(false);
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "Não foi possível carregar o formulário.");
    } finally { setLoading(false); }
  }, [typeId]);

  useEffect(() => { void load(); }, [load]);
  useEffect(() => { if (error) errorRef.current?.focus(); }, [error]);

  const updateField = (index: number, update: Partial<Field>) => {
    setFields((current) => current.map((field, position) => position === index ? { ...field, ...update } : field));
    setDirty(true);
    setNotice("");
  };
  const replaceField = (index: number, next: Field) => {
    setFields((current) => current.map((field, position) => position === index ? next : field));
    setDirty(true);
    setNotice("");
  };
  const changeRules = (index: number, mode: "all" | "any", rules: Condition[]) => {
    updateField(index, { visible_if: { [mode]: rules } });
  };
  const moveField = (index: number, offset: number) => {
    setFields((current) => {
      const next = [...current];
      [next[index], next[index + offset]] = [next[index + offset], next[index]];
      return next;
    });
    setDirty(true);
  };
  const createRevision = () => {
    if (dirty && !window.confirm("Descartar mudanças ainda não salvas nesta tela?")) return;
    setFields((published?.schema.fields ?? []).map((field) => structuredClone(field)));
    setDraftId(null);
    setViewingVersionId(null);
    setSummary("");
    setAnswers({});
    setDirty(false);
    setNotice("Nova revisão iniciada. Salve o rascunho antes de publicar.");
  };
  const selectVersion = (version: Version) => {
    if (dirty && !window.confirm("Descartar mudanças ainda não salvas nesta tela?")) return;
    setDraftId(version.status === "draft" ? version.id : null);
    setViewingVersionId(version.id);
    setFields(version.schema.fields.map((field) => structuredClone(field)));
    setSummary(version.status === "draft" ? version.change_summary ?? "" : "");
    setAnswers({});
    setDirty(false);
    setNotice(version.status !== "draft" ? "Versão histórica em modo de consulta. Use ‘Nova revisão’ para alterar." : "Rascunho carregado.");
  };
  const save = async () => {
    if (readOnly) return;
    setBusy(true); setError(""); setNotice("");
    try {
      const token = sessionStorage.getItem("jwt_token");
      const url = draftId ? `/api/form-versions/${draftId}` : `/api/type-requests/${typeId}/form-versions`;
      const response = await fetch(url, { method: draftId ? "PUT" : "POST", headers: { Authorization: `Bearer ${token ?? ""}`, "Content-Type": "application/json" }, body: JSON.stringify({ schema: { fields }, change_summary: summary.trim() || null }) });
      const data = await response.json();
      if (!response.ok) throw new Error(parseApiError(data));
      const version = data as Version;
      setDraftId(version.id);
      setViewingVersionId(version.id);
      setVersions((current) => [version, ...current.filter((item) => item.id !== version.id)].sort((a, b) => b.version - a.version));
      setDirty(false);
      setNotice(`Rascunho da versão ${version.version} salvo. Ele ainda não está visível para alunos.`);
    } catch (caught) { setError(caught instanceof Error ? caught.message : "Não foi possível salvar."); }
    finally { setBusy(false); }
  };
  const publish = async () => {
    if (!draftId || dirty) { setError("Salve todas as alterações do rascunho antes de publicar."); return; }
    if (!summary.trim()) { setError("Descreva o que mudou antes de publicar."); return; }
    if (!window.confirm("Publicar esta versão para novos requerimentos? Rascunhos de alunos em outra versão precisarão ser revisados antes do envio.")) return;
    setBusy(true); setError(""); setNotice("");
    try {
      const response = await fetch(`/api/form-versions/${draftId}/publish`, { method: "POST", headers: { Authorization: `Bearer ${sessionStorage.getItem("jwt_token") ?? ""}` } });
      const data = await response.json();
      if (!response.ok) throw new Error(parseApiError(data));
      setDraftId(null);
      setViewingVersionId(data.id);
      setVersions((current) => current.map((version) => version.id === draftId ? data : version.status === "published" ? { ...version, status: "superseded" } : version));
      setNotice(`Versão ${data.version} publicada. Novos pedidos usarão esta configuração.`);
    } catch (caught) { setError(caught instanceof Error ? caught.message : "Não foi possível publicar."); }
    finally { setBusy(false); }
  };

  return (
    <div className="min-h-screen bg-slate-50 px-4 py-6 text-slate-900 sm:px-6 sm:py-8">
      <div className="mx-auto max-w-7xl space-y-6">
        <header className="grid grid-cols-[auto_minmax(0,1fr)] items-start gap-4 sm:grid-cols-[auto_minmax(0,1fr)_auto]">
          <Link href="/dashboard/admin/types" aria-label="Voltar aos tipos de requerimento" className={secondaryButton}><ArrowLeft size={18} aria-hidden="true" /></Link>
          <div className="min-w-0 flex-1"><p className="text-xs font-bold uppercase tracking-wider text-emerald-800">Configuração de serviços</p><h1 className="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Formulário de {typeName}</h1><p className="mt-1 text-sm text-slate-600">Edite um rascunho, revise as diferenças e publique somente depois de conferir as regras do serviço.</p></div>
          <button type="button" onClick={createRevision} disabled={loading || busy} className={`${secondaryButton} col-span-2 w-full sm:col-span-1 sm:w-auto`}><Plus size={17} aria-hidden="true" />Nova revisão</button>
        </header>

        {error && <div ref={errorRef} tabIndex={-1} role="alert" className="rounded-xl border border-red-300 bg-red-50 p-4 text-sm font-medium text-red-900">{error}</div>}
        {notice && <div role="status" className="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm font-medium text-emerald-950">{notice}</div>}
        {loading ? <div role="status" className="rounded-xl border border-slate-200 bg-white p-8">Carregando versões do formulário…</div> : (
          <>
            <section aria-label="Versões do formulário" className="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
              <div className="flex flex-wrap items-center gap-3"><h2 className="mr-auto text-base font-bold">Versões</h2><span className="text-sm text-slate-600">Publicada: {published ? `v${published.version}` : "nenhuma"}</span></div>
              <div className="mt-3 flex flex-wrap gap-2">
                {versions.length === 0 ? <p className="text-sm text-slate-600">Nenhuma versão criada. Adicione campos e salve o primeiro rascunho.</p> : versions.map((version) => (
                  <button key={version.id} type="button" onClick={() => selectVersion(version)} className={`min-h-11 rounded-lg border px-3 text-sm font-semibold ${version.id === viewingVersionId ? "border-emerald-700 bg-emerald-50 text-emerald-950" : "border-slate-200 text-slate-700 hover:bg-slate-50"}`}>
                    v{version.version} · {version.status === "draft" ? "Rascunho" : version.status === "published" ? "Publicada" : "Anterior"}
                  </button>
                ))}
              </div>
            </section>

            <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(300px,0.85fr)]">
              <section aria-labelledby="editor-title" className="min-w-0 space-y-5 rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
                <div><h2 id="editor-title" className="text-lg font-bold">Campos do formulário</h2><p className="text-sm text-slate-600">Assunto e descrição continuam obrigatórios no formulário do aluno. Configure aqui apenas os campos adicionais.</p></div>
                {readOnly && <p className="rounded-lg bg-slate-100 p-3 text-sm font-medium text-slate-700">Versão somente para consulta. Clique em “Nova revisão” para alterar.</p>}
                <fieldset disabled={readOnly} className="space-y-5 disabled:opacity-70">
                <div className="space-y-4">
                  {fields.length === 0 && <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">Sem campos adicionais. O aluno verá assunto e descrição.</p>}
                  {fields.map((field, index) => {
                    const condition = field.visible_if;
                    const mode = condition?.all ? "all" : condition?.any ? "any" : "";
                    const rules = condition?.all ?? condition?.any ?? [];
                    return <fieldset key={`${index}-${field.key}`} className="min-w-0 rounded-xl border border-slate-200 p-4">
                      <legend className="px-1 text-sm font-bold text-slate-700">Campo {index + 1}</legend>
                      <div className="mb-4 flex justify-end gap-1">
                        <button type="button" onClick={() => moveField(index, -1)} disabled={index === 0} aria-label={`Mover campo ${index + 1} para cima`} className="min-h-11 min-w-11 rounded-lg hover:bg-slate-100 disabled:opacity-40"><ArrowUp size={17} className="mx-auto" aria-hidden="true" /></button>
                        <button type="button" onClick={() => moveField(index, 1)} disabled={index === fields.length - 1} aria-label={`Mover campo ${index + 1} para baixo`} className="min-h-11 min-w-11 rounded-lg hover:bg-slate-100 disabled:opacity-40"><ArrowDown size={17} className="mx-auto" aria-hidden="true" /></button>
                        <button type="button" onClick={() => { if (window.confirm(`Remover o campo ${field.label || field.key}?`)) { setFields((current) => current.filter((_, position) => position !== index)); setDirty(true); } }} aria-label={`Remover campo ${index + 1}`} className="min-h-11 min-w-11 rounded-lg text-red-700 hover:bg-red-50"><Trash2 size={17} className="mx-auto" aria-hidden="true" /></button>
                      </div>
                      <div className="grid gap-4 sm:grid-cols-2">
                        <label className="block text-sm font-medium">Chave técnica<input value={field.key} onChange={(event) => updateField(index, { key: event.target.value })} maxLength={60} className={`${inputClass} mt-1`} /><span className="mt-1 block text-xs text-slate-600">Letras minúsculas, números e sublinhado; mantenha a chave ao revisar um campo.</span></label>
                        <label className="block text-sm font-medium">Rótulo visível<input value={field.label} onChange={(event) => updateField(index, { label: event.target.value })} maxLength={120} className={`${inputClass} mt-1`} /></label>
                        <label className="block text-sm font-medium">Tipo<select value={field.type} onChange={(event) => { const type = event.target.value as FieldType; const next = { ...field, type }; delete next.options; delete next.min; delete next.max; delete next.max_length; delete next.max_items; if (type === "single_select" || type === "multi_select") next.options = ["Opção 1"]; replaceField(index, next); }} className={`${inputClass} mt-1`}>{fieldTypes.map((type) => <option value={type.value} key={type.value}>{type.label}</option>)}</select></label>
                        <label className="flex min-h-11 items-center gap-2 self-end text-sm font-medium"><input type="checkbox" checked={Boolean(field.required)} onChange={(event) => updateField(index, { required: event.target.checked })} className="size-5 accent-emerald-800" />Resposta obrigatória</label>
                      </div>
                      <label className="mt-4 block text-sm font-medium">Instrução ao aluno<input value={field.instruction ?? ""} onChange={(event) => updateField(index, { instruction: event.target.value })} maxLength={500} className={`${inputClass} mt-1`} /></label>
                      {(field.type === "single_select" || field.type === "multi_select") && <label className="mt-4 block text-sm font-medium">Opções, uma por linha<textarea value={(field.options ?? []).join("\n")} onChange={(event) => updateField(index, { options: event.target.value.split("\n").filter(Boolean) })} rows={4} className={`${inputClass} mt-1`} /></label>}
                      {(field.type === "short_text" || field.type === "long_text") && <label className="mt-4 block text-sm font-medium">Máximo de caracteres<input type="number" min={1} max={10000} value={field.max_length ?? ""} onChange={(event) => { const next = { ...field }; if (event.target.value) next.max_length = Number(event.target.value); else delete next.max_length; replaceField(index, next); }} className={`${inputClass} mt-1`} /></label>}
                      {field.type === "number" && <div className="mt-4 grid gap-4 sm:grid-cols-2">{(["min", "max"] as const).map((limit) => <label key={limit} className="block text-sm font-medium">{limit === "min" ? "Mínimo" : "Máximo"}<input type="number" value={field[limit] ?? ""} onChange={(event) => { const next = { ...field }; if (event.target.value) next[limit] = Number(event.target.value); else delete next[limit]; replaceField(index, next); }} className={`${inputClass} mt-1`} /></label>)}</div>}
                      {field.type === "subjects" && <label className="mt-4 block text-sm font-medium">Máximo de disciplinas<input type="number" min={1} max={20} value={field.max_items ?? ""} onChange={(event) => { const next = { ...field }; if (event.target.value) next.max_items = Number(event.target.value); else delete next.max_items; replaceField(index, next); }} className={`${inputClass} mt-1`} /></label>}
                      {index > 0 && <div className="mt-4 border-t border-slate-200 pt-4">
                        <h3 className="text-sm font-bold">Visibilidade condicional</h3>
                        <p className="text-xs text-slate-600">Use somente campos anteriores. As condições são verificadas também pelo servidor.</p>
                        <label className="mt-3 block text-sm font-medium">Exibir campo<select value={mode} onChange={(event) => {
                          const nextMode = event.target.value as "all" | "any" | "";
                          if (!nextMode) { const next = { ...field }; delete next.visible_if; replaceField(index, next); }
                          else changeRules(index, nextMode, rules.length ? rules : [{ field: fields[0].key, operator: "present" }]);
                        }} className={`${inputClass} mt-1`}><option value="">Sempre</option><option value="all">Se todas as regras forem verdadeiras</option><option value="any">Se qualquer regra for verdadeira</option></select></label>
                        {mode && <div className="mt-3 space-y-3">{rules.map((rule, ruleIndex) => {
                          const source = fields.slice(0, index).find((candidate) => candidate.key === rule.field);
                          const changeRule = (next: Condition) => changeRules(index, mode, rules.map((current, position) => position === ruleIndex ? next : current));
                          return <div key={ruleIndex} className="grid gap-3 rounded-lg bg-slate-50 p-3 sm:grid-cols-2">
                            <label className="text-sm font-medium">Campo anterior<select value={rule.field} onChange={(event) => changeRule({ field: event.target.value, operator: "present" })} className={`${inputClass} mt-1`}>{fields.slice(0, index).map((earlier) => <option key={earlier.key} value={earlier.key}>{earlier.label || earlier.key}</option>)}</select></label>
                            <label className="text-sm font-medium">Comparação<select value={rule.operator} onChange={(event) => { const operator = event.target.value as Condition["operator"]; changeRule({ field: rule.field, operator, ...(operator === "equals" ? { value: "" } : operator === "in" ? { value: [] } : {}) }); }} className={`${inputClass} mt-1`}><option value="present">Tem resposta</option><option value="equals">É igual a</option><option value="in">Está na lista</option></select></label>
                            {rule.operator !== "present" && <label className="text-sm font-medium">{rule.operator === "in" ? "Valores separados por vírgula" : "Valor"}{source?.type === "boolean" ? <select value={String(Array.isArray(rule.value) ? rule.value[0] ?? "" : rule.value ?? "")} onChange={(event) => changeRule({ ...rule, value: rule.operator === "in" ? [conditionValue(event.target.value, source)] : conditionValue(event.target.value, source) })} className={`${inputClass} mt-1`}><option value="">Selecione</option><option value="true">Sim</option><option value="false">Não</option></select> : <input value={Array.isArray(rule.value) ? rule.value.join(", ") : String(rule.value ?? "")} onChange={(event) => changeRule({ ...rule, value: rule.operator === "in" ? event.target.value.split(",").map((value) => conditionValue(value.trim(), source)).filter((value) => value !== "") : conditionValue(event.target.value, source) })} className={`${inputClass} mt-1`} />}</label>}
                            <button type="button" onClick={() => { if (rules.length === 1) { const next = { ...field }; delete next.visible_if; replaceField(index, next); } else changeRules(index, mode, rules.filter((_, position) => position !== ruleIndex)); }} className="min-h-11 self-end rounded-lg text-sm font-semibold text-red-800 hover:bg-red-50">Remover condição</button>
                          </div>;
                        })}<button type="button" disabled={rules.length >= 10} onClick={() => changeRules(index, mode, [...rules, { field: fields[0].key, operator: "present" }])} className={secondaryButton}><Plus size={16} aria-hidden="true" />Adicionar condição</button></div>}
                      </div>}
                    </fieldset>;
                  })}
                </div>
                <button type="button" onClick={() => { setFields((current) => [...current, { key: `campo_${current.length + 1}`, label: "", type: "short_text", required: false }]); setDirty(true); }} disabled={fields.length >= 50} className={secondaryButton}><Plus size={17} aria-hidden="true" />Adicionar campo</button>
                <label className="block text-sm font-medium">Resumo da mudança<textarea value={summary} onChange={(event) => { setSummary(event.target.value); setDirty(true); }} rows={3} maxLength={1000} className={`${inputClass} mt-1`} placeholder="Explique o que mudou e por quê" /><span className="mt-1 block text-xs text-slate-600">A descrição acompanha o histórico das versões.</span></label>
                <div className="flex flex-wrap gap-3 border-t border-slate-200 pt-5"><button type="button" onClick={save} disabled={busy || !dirty && Boolean(draftId)} className={primaryButton}><Save size={17} aria-hidden="true" />{busy ? "Aguarde…" : "Salvar rascunho"}</button><button type="button" onClick={publish} disabled={busy || !draftId || dirty} className={secondaryButton}><Send size={17} aria-hidden="true" />Publicar versão</button></div>
                </fieldset>
              </section>

              <div className="min-w-0 space-y-6 lg:sticky lg:top-6">
                <section aria-labelledby="comparison-title" className="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6"><h2 id="comparison-title" className="text-lg font-bold">Comparação com a versão publicada</h2><p className="mt-1 text-sm text-slate-600">Pedidos enviados mantêm a versão original. Rascunhos de alunos em versão anterior precisarão de revisão.</p><dl className="mt-4 space-y-3 text-sm"><div><dt className="font-bold text-emerald-900">Adicionados</dt><dd className="text-slate-700">{comparison.added.join(", ") || "Nenhum"}</dd></div><div><dt className="font-bold text-amber-800">Alterados</dt><dd className="text-slate-700">{comparison.changed.join(", ") || "Nenhum"}</dd></div><div><dt className="font-bold text-red-800">Removidos</dt><dd className="text-slate-700">{comparison.removed.join(", ") || "Nenhum"}</dd></div></dl></section>
                <section aria-labelledby="preview-title" className="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6"><h2 id="preview-title" className="flex items-center gap-2 text-lg font-bold"><Eye size={18} aria-hidden="true" />Prévia com dados fictícios</h2><p className="mt-1 text-sm text-slate-600">Esta prévia não cria requerimento nem protocolo.</p><div className="mt-5 space-y-4"><label className="block text-sm font-medium">Assunto<input disabled value="Exemplo de assunto" className={`${inputClass} mt-1 bg-slate-50`} /></label><label className="block text-sm font-medium">Descrição<textarea disabled value="Exemplo de descrição" rows={2} className={`${inputClass} mt-1 bg-slate-50`} /></label>{fields.filter((field) => visible(field, answers)).map((field, index) => <div key={`${field.key}-${index}`} className="min-w-0"><label className="block text-sm font-medium">{field.label || field.key}{field.required ? " *" : ""}{field.type === "short_text" && <input value={String(answers[field.key] ?? "")} onChange={(event) => setAnswers((current) => ({ ...current, [field.key]: event.target.value }))} className={`${inputClass} mt-1`} />}{field.type === "long_text" && <textarea value={String(answers[field.key] ?? "")} onChange={(event) => setAnswers((current) => ({ ...current, [field.key]: event.target.value }))} rows={3} className={`${inputClass} mt-1`} />}{field.type === "number" && <input type="number" value={String(answers[field.key] ?? "")} onChange={(event) => setAnswers((current) => ({ ...current, [field.key]: event.target.value ? Number(event.target.value) : "" }))} className={`${inputClass} mt-1`} />}{field.type === "date" && <input type="date" value={String(answers[field.key] ?? "")} onChange={(event) => setAnswers((current) => ({ ...current, [field.key]: event.target.value }))} className={`${inputClass} mt-1`} />}{field.type === "single_select" && <select value={String(answers[field.key] ?? "")} onChange={(event) => setAnswers((current) => ({ ...current, [field.key]: event.target.value }))} className={`${inputClass} mt-1`}><option value="">Selecione</option>{field.options?.map((option) => <option key={option}>{option}</option>)}</select>}{field.type === "boolean" && <select value={String(answers[field.key] ?? "")} onChange={(event) => setAnswers((current) => ({ ...current, [field.key]: event.target.value === "true" }))} className={`${inputClass} mt-1`}><option value="">Selecione</option><option value="true">Sim</option><option value="false">Não</option></select>}{field.type === "multi_select" && <select multiple value={Array.isArray(answers[field.key]) ? answers[field.key] as string[] : []} onChange={(event) => setAnswers((current) => ({ ...current, [field.key]: Array.from(event.target.selectedOptions, (option) => option.value) }))} className={`${inputClass} mt-1 min-h-28`}>{field.options?.map((option) => <option key={option}>{option}</option>)}</select>}{field.type === "subjects" && <input value={String(answers[field.key] ?? "")} onChange={(event) => setAnswers((current) => ({ ...current, [field.key]: event.target.value }))} placeholder="Exemplo: Matemática I" className={`${inputClass} mt-1`} />}</label>{field.instruction && <p className="mt-1 text-xs text-slate-600">{field.instruction}</p>}</div>)}</div></section>
              </div>
            </div>
          </>
        )}
      </div>
    </div>
  );
}

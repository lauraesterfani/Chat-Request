"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";
import Link from "next/link";

type AccessScope = {
  id: number;
  staff_admin_id: number;
  campus_id?: string | null;
  course_id?: string | null;
  sector?: string | null;
  abilities: string[];
  starts_at?: string | null;
  expires_at?: string | null;
  revoked_at?: string | null;
  reason: string;
  staff_admin?: { name: string; role: string };
  campus?: { id: string; name: string; code: string } | null;
  course?: { id: string; name: string; code: string; campus_id?: string | null } | null;
};

type StaffOption = { id: number; name: string; role: "cradt" | "coordenacao" };
type CampusOption = { id: string; name: string; code: string; is_active?: boolean; courses_count?: number };
type CourseOption = { id: string; name: string; code: string; campus_id?: string | null };
type Options = { staff: StaffOption[]; campuses: CampusOption[]; courses: CourseOption[]; sectors: string[] };

const abilityLabels: Record<string, string> = {
  view_request: "Consultar requerimentos",
  reply_request: "Responder ao aluno",
  review_document: "Revisar documentos",
  forward_request: "Encaminhar requerimentos",
  decide_request: "Registrar decisão final",
  use_templates: "Usar respostas pré-configuradas",
  manage_templates: "Gerenciar respostas pré-configuradas",
};
const coordinatorAbilities = ["view_request", "reply_request", "review_document", "use_templates"];

function localDateTime(minutesAhead = 3): string {
  const date = new Date(Date.now() + minutesAhead * 60_000);
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
  return date.toISOString().slice(0, 16);
}

function toUtcIso(value: string): string | null {
  if (!value) return null;
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? null : date.toISOString();
}

function describeError(payload: { message?: string; errors?: Record<string, string[]> }): string {
  return payload.message ?? Object.values(payload.errors ?? {}).flat()[0] ?? "Não foi possível concluir a operação.";
}

export default function AccessScopesPage() {
  const [scopes, setScopes] = useState<AccessScope[]>([]);
  const [campuses, setCampuses] = useState<CampusOption[]>([]);
  const [options, setOptions] = useState<Options>({ staff: [], campuses: [], courses: [], sectors: [] });
  const [message, setMessage] = useState("");
  const [isError, setIsError] = useState(false);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [campusForm, setCampusForm] = useState({ name: "", code: "" });
  const [courseAssignment, setCourseAssignment] = useState({ course_id: "", campus_id: "" });
  const [form, setForm] = useState({ staff_admin_id: "", campus_id: "", course_id: "", sector: "", abilities: ["view_request"], starts_at: "", expires_at: "", reason: "" });
  const token = () => sessionStorage.getItem("jwt_token") ?? "";

  const load = useCallback(async () => {
    try {
      const headers = { Authorization: `Bearer ${token()}` };
      const [scopeResponse, optionsResponse] = await Promise.all([
        fetch("/api/staff-access-scopes?per_page=100", { headers, cache: "no-store" }),
        fetch("/api/staff-access-scopes/options", { headers, cache: "no-store" }),
      ]);
      const campusesResponse = await fetch("/api/admin/campuses?per_page=100", { headers, cache: "no-store" });
      if (!scopeResponse.ok || !optionsResponse.ok || !campusesResponse.ok) throw new Error("Você não possui acesso a esta área.");
      const [scopeData, optionData, campusData] = await Promise.all([scopeResponse.json(), optionsResponse.json(), campusesResponse.json()]);
      setScopes(scopeData.data ?? []);
      setOptions(optionData);
      setCampuses(campusData.data ?? []);
      setMessage("");
      setIsError(false);
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Não foi possível carregar os escopos.");
      setIsError(true);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { void load(); }, [load]);

  const availableAbilities = options.staff.find((staff) => String(staff.id) === form.staff_admin_id)?.role === "coordenacao"
    ? coordinatorAbilities
    : Object.keys(abilityLabels);
  const availableCourses = options.courses.filter((course) => !form.campus_id || course.campus_id === form.campus_id);

  const save = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const submittedValues = new FormData(event.currentTarget);
    const submittedStart = String(submittedValues.get("starts_at") ?? "");
    const submittedExpiry = String(submittedValues.get("expires_at") ?? "");
    setMessage("");
    setIsError(false);
    setSaving(true);
    try {
      const response = await fetch("/api/staff-access-scopes", {
        method: "POST",
        headers: { Authorization: `Bearer ${token()}`, "Content-Type": "application/json" },
        body: JSON.stringify({
          ...form,
          staff_admin_id: Number(form.staff_admin_id),
          campus_id: form.campus_id || null,
          course_id: form.course_id || null,
          sector: form.sector || null,
          starts_at: toUtcIso(submittedStart),
          expires_at: toUtcIso(submittedExpiry),
        }),
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(describeError(payload));
      setMessage("Acesso concedido e registrado.");
      setForm({ staff_admin_id: "", campus_id: "", course_id: "", sector: "", abilities: ["view_request"], starts_at: localDateTime(), expires_at: "", reason: "" });
      await load();
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Não foi possível conceder o acesso.");
      setIsError(true);
    } finally {
      setSaving(false);
    }
  };

  const saveCampus = async (event: FormEvent) => {
    event.preventDefault();
    const response = await fetch("/api/admin/campuses", {
      method: "POST",
      headers: { Authorization: `Bearer ${token()}`, "Content-Type": "application/json" },
      body: JSON.stringify(campusForm),
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      setMessage(describeError(payload));
      setIsError(true);
      return;
    }
    setCampusForm({ name: "", code: "" });
    setMessage("Campus cadastrado.");
    setIsError(false);
    await load();
  };

  const updateCampusStatus = async (campus: CampusOption) => {
    const response = await fetch(`/api/admin/campuses/${campus.id}`, {
      method: "PATCH",
      headers: { Authorization: `Bearer ${token()}`, "Content-Type": "application/json" },
      body: JSON.stringify({ is_active: !campus.is_active }),
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      setMessage(describeError(payload));
      setIsError(true);
      return;
    }
    setMessage(campus.is_active ? "Campus desativado." : "Campus ativado.");
    setIsError(false);
    await load();
  };

  const assignCourse = async (event: FormEvent) => {
    event.preventDefault();
    const response = await fetch(`/api/admin/courses/${courseAssignment.course_id}/campus`, {
      method: "PUT",
      headers: { Authorization: `Bearer ${token()}`, "Content-Type": "application/json" },
      body: JSON.stringify({ campus_id: courseAssignment.campus_id || null }),
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      setMessage(describeError(payload));
      setIsError(true);
      return;
    }
    setMessage("Vínculo do curso atualizado.");
    setIsError(false);
    await load();
  };

  const revoke = async (scope: AccessScope) => {
    if (!window.confirm(`Revogar o acesso de ${scope.staff_admin?.name ?? "este usuário"}? O registro permanecerá no histórico.`)) return;
    const response = await fetch(`/api/staff-access-scopes/${scope.id}`, { method: "DELETE", headers: { Authorization: `Bearer ${token()}` } });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      setMessage(describeError(payload));
      setIsError(true);
      return;
    }
    setMessage("Acesso revogado; o registro foi preservado no histórico.");
    setIsError(false);
    await load();
  };

  const statusLabel = (scope: AccessScope) => {
    if (scope.revoked_at) return "Revogado";
    if (scope.starts_at && new Date(scope.starts_at) > new Date()) return "Agendado";
    if (scope.expires_at && new Date(scope.expires_at) <= new Date()) return "Expirado";
    return "Ativo";
  };

  return (
    <main className="mx-auto max-w-5xl space-y-6 p-4 sm:p-8">
      <header>
        <Link href="/dashboard/admin" className="text-sm text-emerald-700">← Painel</Link>
        <h1 className="mt-2 text-3xl font-bold text-slate-800">Escopos de acesso</h1>
        <p className="mt-1 max-w-3xl text-sm text-slate-600">Conceda capacidades com limites de campus, curso ou setor. O acesso só começa no horário indicado; a revogação preserva o histórico. Este cadastro não substitui a matriz institucional homologada.</p>
      </header>

      {message && <p role={isError ? "alert" : "status"} className={`rounded-lg p-3 text-sm ${isError ? "bg-red-50 text-red-800" : "bg-emerald-50 text-emerald-800"}`}>{message}</p>}

      <section className="rounded-2xl border bg-white p-5 shadow-sm sm:p-6">
        <h2 className="font-bold text-slate-900">Campi e vínculos dos cursos</h2>
        <p className="mt-1 text-sm text-slate-600">Cadastre somente campi oficiais e associe os cursos conforme a matriz institucional aprovada.</p>
        <form onSubmit={saveCampus} className="mt-4 grid gap-3 sm:grid-cols-[1fr_180px_auto]">
          <label className="text-sm font-medium">Nome do campus
            <input required maxLength={160} value={campusForm.name} onChange={(event) => setCampusForm({ ...campusForm, name: event.target.value })} className="mt-1 min-h-11 w-full rounded-lg border border-slate-300 p-2.5" />
          </label>
          <label className="text-sm font-medium">Código
            <input required maxLength={40} value={campusForm.code} onChange={(event) => setCampusForm({ ...campusForm, code: event.target.value.toUpperCase() })} className="mt-1 min-h-11 w-full rounded-lg border border-slate-300 p-2.5" />
          </label>
          <button disabled={saving || loading} className="min-h-11 self-end rounded-lg bg-emerald-800 px-4 py-2 font-semibold text-white disabled:opacity-60">Cadastrar campus</button>
        </form>
        <form onSubmit={assignCourse} className="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-[1fr_1fr_auto]">
          <label className="text-sm font-medium">Curso
            <select required value={courseAssignment.course_id} onChange={(event) => setCourseAssignment({ ...courseAssignment, course_id: event.target.value })} className="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white p-2.5">
              <option value="">Selecione um curso</option>
              {options.courses.map((course) => <option key={course.id} value={course.id}>{course.name} · {course.code}</option>)}
            </select>
          </label>
          <label className="text-sm font-medium">Campus
            <select value={courseAssignment.campus_id} onChange={(event) => setCourseAssignment({ ...courseAssignment, campus_id: event.target.value })} className="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white p-2.5">
              <option value="">Sem campus associado</option>
              {campuses.filter((campus) => campus.is_active).map((campus) => <option key={campus.id} value={campus.id}>{campus.name} · {campus.code}</option>)}
            </select>
          </label>
          <button disabled={!courseAssignment.course_id || loading} className="min-h-11 self-end rounded-lg border border-emerald-800 px-4 py-2 font-semibold text-emerald-900 disabled:opacity-50">Salvar vínculo</button>
        </form>
        <ul className="mt-4 divide-y divide-slate-100">
          {campuses.map((campus) => <li key={campus.id} className="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
            <span className="text-sm text-slate-800">{campus.name} <span className="text-slate-500">· {campus.code} · {campus.courses_count ?? 0} {(campus.courses_count ?? 0) === 1 ? "curso" : "cursos"}</span></span>
            <button onClick={() => void updateCampusStatus(campus)} className="min-h-10 self-start rounded-lg border border-slate-300 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:self-auto">{campus.is_active ? "Desativar" : "Ativar"}</button>
          </li>)}
          {!loading && campuses.length === 0 && <li className="py-3 text-sm text-slate-500">Nenhum campus cadastrado. Use os dados oficiais fornecidos pela instituição.</li>}
        </ul>
      </section>

      <section className="rounded-2xl border bg-white p-5 shadow-sm sm:p-6">
        <h2 className="font-bold text-slate-900">Conceder acesso restrito</h2>
        <p className="mt-1 text-sm text-slate-600">Escolha a pessoa e ao menos um limite institucional. Não é possível conceder acesso global por esta tela.</p>
        <form onSubmit={save} className="mt-5 grid gap-4 sm:grid-cols-2">
          <label className="text-sm font-medium">Pessoa da equipe
            <select required value={form.staff_admin_id} onChange={(event) => setForm({ ...form, staff_admin_id: event.target.value, abilities: ["view_request"] })} className="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white p-2.5">
              <option value="">Selecione uma pessoa</option>
              {options.staff.map((staff) => <option key={staff.id} value={staff.id}>{staff.name} · {staff.role === "cradt" ? "CRADT" : "Coordenação"}</option>)}
            </select>
          </label>
          <label className="text-sm font-medium">Campus (opcional)
            <select value={form.campus_id} onChange={(event) => setForm({ ...form, campus_id: event.target.value, course_id: "" })} className="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white p-2.5">
              <option value="">Sem limite de campus</option>
              {options.campuses.map((campus) => <option key={campus.id} value={campus.id}>{campus.name} · {campus.code}</option>)}
            </select>
          </label>
          <label className="text-sm font-medium">Curso (opcional)
            <select value={form.course_id} onChange={(event) => setForm({ ...form, course_id: event.target.value })} className="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white p-2.5">
              <option value="">Sem limite de curso</option>
              {availableCourses.map((course) => <option key={course.id} value={course.id}>{course.name} · {course.code}</option>)}
            </select>
          </label>
          <label className="text-sm font-medium">Setor (opcional)
            <select value={form.sector} onChange={(event) => setForm({ ...form, sector: event.target.value })} className="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white p-2.5">
              <option value="">Sem limite de setor</option>
              {options.sectors.map((sector) => <option key={sector} value={sector}>{sector === "CRADT" ? "CRADT" : "Coordenação"}</option>)}
            </select>
          </label>
          <label className="text-sm font-medium">Início do acesso
            <input required name="starts_at" type="datetime-local" value={form.starts_at} onChange={(event) => setForm({ ...form, starts_at: event.target.value })} className="mt-1 min-h-11 w-full rounded-lg border border-slate-300 p-2.5" />
          </label>
          <label className="text-sm font-medium">Fim do acesso (opcional)
            <input name="expires_at" type="datetime-local" value={form.expires_at} onChange={(event) => setForm({ ...form, expires_at: event.target.value })} className="mt-1 min-h-11 w-full rounded-lg border border-slate-300 p-2.5" />
          </label>
          <fieldset className="sm:col-span-2">
            <legend className="text-sm font-medium">Capacidades permitidas</legend>
            <div className="mt-2 grid gap-2 sm:grid-cols-2">
              {availableAbilities.map((ability) => <label key={ability} className="flex min-h-11 items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm">
                <input type="checkbox" checked={form.abilities.includes(ability)} onChange={(event) => setForm({ ...form, abilities: event.target.checked ? [...form.abilities, ability] : form.abilities.filter((item) => item !== ability) })} />
                {abilityLabels[ability]}
              </label>)}
            </div>
          </fieldset>
          <label className="text-sm font-medium sm:col-span-2">Justificativa
            <textarea required maxLength={1000} value={form.reason} onChange={(event) => setForm({ ...form, reason: event.target.value })} className="mt-1 min-h-20 w-full rounded-lg border border-slate-300 p-3" />
          </label>
          <button disabled={saving || loading} className="min-h-11 rounded-xl bg-emerald-800 px-5 py-3 font-bold text-white disabled:cursor-not-allowed disabled:opacity-60 sm:col-span-2">{saving ? "Salvando…" : "Conceder acesso"}</button>
        </form>
      </section>

      <section className="overflow-hidden rounded-2xl border bg-white shadow-sm">
        <h2 className="border-b p-5 font-bold text-slate-900">Histórico de concessões</h2>
        {loading ? <p className="p-5 text-sm text-slate-600">Carregando concessões…</p> : scopes.length === 0 ? <p className="p-5 text-sm text-slate-600">Nenhuma concessão registrada.</p> : <div className="divide-y">{scopes.map((scope) => {
          const status = statusLabel(scope);
          return <article key={scope.id} className="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-2"><p className="font-semibold text-slate-900">{scope.staff_admin?.name ?? `Equipe #${scope.staff_admin_id}`}</p><span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${status === "Ativo" ? "bg-emerald-100 text-emerald-800" : "bg-slate-100 text-slate-700"}`}>{status}</span></div>
              <p className="mt-1 text-sm text-slate-700">{scope.abilities.map((ability) => abilityLabels[ability] ?? ability).join(" · ")}</p>
              <p className="text-sm text-slate-600">{scope.campus?.name ?? "Todos os campi cadastrados"} · {scope.course?.name ?? "Todos os cursos"} · {scope.sector ?? "Todos os setores"}</p>
              <p className="text-xs text-slate-500">Início: {scope.starts_at ? new Date(scope.starts_at).toLocaleString("pt-BR") : "legado"} · Fim: {scope.expires_at ? new Date(scope.expires_at).toLocaleString("pt-BR") : "sem expiração"}{scope.revoked_at ? ` · Revogado: ${new Date(scope.revoked_at).toLocaleString("pt-BR")}` : ""}</p>
              <p className="mt-1 break-words text-sm text-slate-700">{scope.reason}</p>
            </div>
            {["Ativo", "Agendado"].includes(status) && <button onClick={() => void revoke(scope)} className="min-h-11 shrink-0 rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Revogar</button>}
          </article>;
        })}</div>}
      </section>
    </main>
  );
}

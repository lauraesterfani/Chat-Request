"use client";

import { FormEvent, useCallback, useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { ArrowLeft, CalendarDays, Check, Clock3, LoaderCircle, Pencil, Plus, RotateCw, Save, ShieldCheck } from "lucide-react";

type WindowStatus = "draft" | "published" | "inactive";
type ServiceOption = { id: string; name: string };
type CampusOption = { id: string; name: string; code: string; is_active?: boolean };
type CourseOption = { id: string; name: string; code: string; campus_id: string | null; is_active?: boolean };
type OpeningWindow = {
  id: string;
  type_request_id: string;
  campus_id: string | null;
  course_id: string | null;
  starts_at: string;
  ends_at: string;
  timezone: string;
  status: WindowStatus;
  source_reference: string | null;
  timestamps_utc_normalized: boolean;
};
type CalendarForm = {
  type_request_id: string;
  campus_id: string;
  course_id: string;
  starts_at: string;
  ends_at: string;
  timezone: string;
  status: WindowStatus;
  source_reference: string;
};

const TIMEZONES = [
  "America/Recife",
  "America/Fortaleza",
  "America/Sao_Paulo",
  "America/Manaus",
  "America/Rio_Branco",
  "America/Noronha",
  "UTC",
];

const EMPTY_FORM: CalendarForm = {
  type_request_id: "",
  campus_id: "",
  course_id: "",
  starts_at: "",
  ends_at: "",
  timezone: "America/Recife",
  status: "draft",
  source_reference: "",
};

const STATUS_LABEL: Record<WindowStatus, string> = {
  draft: "Rascunho",
  published: "Publicada",
  inactive: "Desativada",
};

const STATUS_STYLE: Record<WindowStatus, string> = {
  draft: "border-amber-200 bg-amber-50 text-amber-900",
  published: "border-emerald-200 bg-emerald-50 text-emerald-900",
  inactive: "border-slate-200 bg-slate-100 text-slate-700",
};

function errorMessage(payload: { message?: string; errors?: Record<string, string[]> }, fallback: string) {
  return Object.values(payload.errors ?? {}).flat()[0] ?? payload.message ?? fallback;
}

function getLocalParts(timestamp: number, timezone: string) {
  const parts = new Intl.DateTimeFormat("en-CA", {
    timeZone: timezone,
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    hourCycle: "h23",
  }).formatToParts(new Date(timestamp));
  const value = Object.fromEntries(parts.map((part) => [part.type, part.value]));
  return {
    year: Number(value.year),
    month: Number(value.month),
    day: Number(value.day),
    hour: Number(value.hour),
    minute: Number(value.minute),
  };
}

function zonedInputToUtc(localValue: string, timezone: string): string {
  const match = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/.exec(localValue);
  if (!match) throw new Error("Informe uma data e hora válidas.");
  const desired = { year: Number(match[1]), month: Number(match[2]), day: Number(match[3]), hour: Number(match[4]), minute: Number(match[5]) };
  const desiredAsUtc = Date.UTC(desired.year, desired.month - 1, desired.day, desired.hour, desired.minute);
  let center = desiredAsUtc;
  for (let attempt = 0; attempt < 4; attempt += 1) {
    const actual = getLocalParts(center, timezone);
    const actualAsUtc = Date.UTC(actual.year, actual.month - 1, actual.day, actual.hour, actual.minute);
    center += desiredAsUtc - actualAsUtc;
  }

  const candidates: number[] = [];
  const fifteenMinutes = 15 * 60_000;
  for (let offset = -16; offset <= 16; offset += 1) {
    const candidate = center + offset * fifteenMinutes;
    const actual = getLocalParts(candidate, timezone);
    if (actual.year === desired.year && actual.month === desired.month && actual.day === desired.day && actual.hour === desired.hour && actual.minute === desired.minute) {
      candidates.push(candidate);
    }
  }
  if (candidates.length === 0) throw new Error("Esse horário não existe no fuso escolhido por causa da mudança de horário local.");
  if (candidates.length > 1) throw new Error("Esse horário ocorre duas vezes no fuso escolhido. Selecione outro horário para evitar ambiguidade.");
  return new Date(candidates[0]).toISOString();
}

function utcToZonedInput(value: string, timezone: string): string {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "";
  const parts = getLocalParts(date.getTime(), timezone);
  return `${parts.year}-${String(parts.month).padStart(2, "0")}-${String(parts.day).padStart(2, "0")}T${String(parts.hour).padStart(2, "0")}:${String(parts.minute).padStart(2, "0")}`;
}

function formatWindowTime(value: string, timezone: string): string {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "Data não reconhecida";
  const local = new Intl.DateTimeFormat("pt-BR", { dateStyle: "medium", timeStyle: "short", timeZone: timezone }).format(date);
  const utc = new Intl.DateTimeFormat("pt-BR", { hour: "2-digit", minute: "2-digit", timeZone: "UTC", timeZoneName: "short" }).format(date);
  return `${local} · ${timezone} · ${utc}`;
}

function scopeKey(window: Pick<OpeningWindow, "type_request_id" | "campus_id" | "course_id">) {
  return [window.type_request_id, window.campus_id ?? "*", window.course_id ?? "*"].join("|");
}

export default function ServiceCalendarPage() {
  const router = useRouter();
  const [services, setServices] = useState<ServiceOption[]>([]);
  const [campuses, setCampuses] = useState<CampusOption[]>([]);
  const [courses, setCourses] = useState<CourseOption[]>([]);
  const [windows, setWindows] = useState<OpeningWindow[]>([]);
  const [form, setForm] = useState<CalendarForm>(EMPTY_FORM);
  const [editing, setEditing] = useState<OpeningWindow | null>(null);
  const [filterService, setFilterService] = useState("");
  const [filterCampus, setFilterCampus] = useState("");
  const [filterCourse, setFilterCourse] = useState("");
  const [filterStatus, setFilterStatus] = useState<WindowStatus | "">("");
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [pageError, setPageError] = useState("");
  const [formError, setFormError] = useState("");
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [notice, setNotice] = useState("");

  const loadData = useCallback(async () => {
    setLoading(true);
    setPageError("");
    const token = sessionStorage.getItem("jwt_token");
    if (!token) {
      router.replace("/cradt-login");
      return;
    }
    const headers = { Accept: "application/json", Authorization: `Bearer ${token}` };
    try {
      const meResponse = await fetch("/api/me", { headers, cache: "no-store" });
      const me = await meResponse.json().catch(() => ({}));
      if (meResponse.status === 401) {
        sessionStorage.removeItem("jwt_token");
        router.replace("/cradt-login");
        return;
      }
      if (!meResponse.ok) throw new Error(me.message ?? "Não foi possível validar seu acesso.");
      if (me.role !== "admin") throw new Error("A configuração de janelas exige uma conta administradora institucional com acesso global a campi e cursos.");

      const responses = await Promise.all([
        fetch("/api/admin/type-requests", { headers, cache: "no-store" }),
        fetch("/api/staff-access-scopes/options", { headers, cache: "no-store" }),
        fetch("/api/admin/service-opening-windows", { headers, cache: "no-store" }),
      ]);
      const payloads = await Promise.all(responses.map((response) => response.json().catch(() => ({}))));
      const failed = responses.findIndex((response) => !response.ok);
      if (failed >= 0) {
        if (responses[failed].status === 401) {
          sessionStorage.removeItem("jwt_token");
          router.replace("/cradt-login");
          return;
        }
        throw new Error(errorMessage(payloads[failed], "Não foi possível carregar os serviços, campi e janelas de atendimento."));
      }

      setServices(Array.isArray(payloads[0]) ? payloads[0] : []);
      setCampuses(payloads[1].campuses ?? []);
      setCourses(payloads[1].courses ?? []);
      setWindows(Array.isArray(payloads[2]) ? payloads[2] : []);
    } catch (reason) {
      setPageError(reason instanceof Error ? reason.message : "Não foi possível carregar o calendário de atendimento.");
    } finally {
      setLoading(false);
    }
  }, [router]);

  useEffect(() => { void loadData(); }, [loadData]);

  const visibleWindows = useMemo(() => windows.filter((window) =>
    (!filterService || window.type_request_id === filterService)
    && (!filterCampus || window.campus_id === filterCampus)
    && (!filterCourse || window.course_id === filterCourse)
    && (!filterStatus || window.status === filterStatus)
  ).sort((a, b) => b.starts_at.localeCompare(a.starts_at)), [filterCampus, filterCourse, filterService, filterStatus, windows]);

  const formCourses = useMemo(() => courses.filter((course) => !form.campus_id || course.campus_id === form.campus_id), [courses, form.campus_id]);
  const filterCourses = useMemo(() => courses.filter((course) => !filterCampus || course.campus_id === filterCampus), [courses, filterCampus]);

  const resetForm = () => {
    setForm(EMPTY_FORM);
    setEditing(null);
    setFormError("");
    setFieldErrors({});
    setNotice("");
  };

  const editWindow = (window: OpeningWindow) => {
    setEditing(window);
    setForm({
      type_request_id: window.type_request_id,
      campus_id: window.campus_id ?? "",
      course_id: window.course_id ?? "",
      starts_at: utcToZonedInput(window.starts_at, window.timezone),
      ends_at: utcToZonedInput(window.ends_at, window.timezone),
      timezone: window.timezone,
      status: window.status,
      source_reference: window.source_reference ?? "",
    });
    setFormError("");
    setFieldErrors({});
    setNotice("");
    document.getElementById("calendar-form-title")?.focus();
  };

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setFormError("");
    setFieldErrors({});
    setNotice("");

    const localErrors: Record<string, string> = {};
    if (!form.type_request_id) localErrors.type_request_id = "Selecione um serviço.";
    if (!form.starts_at) localErrors.starts_at = "Informe o início da janela.";
    if (!form.ends_at) localErrors.ends_at = "Informe o encerramento da janela.";
    if (form.status === "published" && !form.source_reference.trim()) localErrors.source_reference = "Informe a referência institucional antes de publicar.";
    if (Object.keys(localErrors).length > 0) {
      setFieldErrors(localErrors);
      setFormError("Revise os campos indicados antes de salvar.");
      return;
    }

    setSaving(true);
    try {
      const startsAt = zonedInputToUtc(form.starts_at, form.timezone);
      const endsAt = zonedInputToUtc(form.ends_at, form.timezone);
      if (new Date(endsAt).getTime() <= new Date(startsAt).getTime()) {
        setFieldErrors({ ends_at: "O encerramento deve ocorrer depois do início, considerando o fuso escolhido." });
        setFormError("O período informado não é válido.");
        return;
      }

      const payload: Omit<OpeningWindow, "id" | "timestamps_utc_normalized"> = {
        type_request_id: form.type_request_id,
        campus_id: form.campus_id || null,
        course_id: form.course_id || null,
        starts_at: startsAt,
        ends_at: endsAt,
        timezone: form.timezone,
        status: form.status,
        source_reference: form.status === "published" ? form.source_reference.trim() : (form.source_reference.trim() || null),
      };

      const candidateScope = { type_request_id: payload.type_request_id, campus_id: payload.campus_id, course_id: payload.course_id };
      const duplicate = windows.find((window) => {
        if (window.id === editing?.id || scopeKey(window) !== scopeKey(candidateScope)) return false;
        const existingStart = new Date(window.starts_at).getTime();
        const existingEnd = new Date(window.ends_at).getTime();
        const nextStart = new Date(startsAt).getTime();
        const nextEnd = new Date(endsAt).getTime();
        if (form.status === "published" && window.status === "published") return existingStart < nextEnd && existingEnd > nextStart;
        return window.status === form.status && existingStart === nextStart && existingEnd === nextEnd;
      });
      if (duplicate) {
        setFormError(form.status === "published"
          ? "Já existe uma janela publicada que se sobrepõe para este serviço, campus e curso."
          : "Já existe uma janela com este serviço, escopo e período.");
        return;
      }

      const token = sessionStorage.getItem("jwt_token");
      if (!token) {
        router.replace("/cradt-login");
        return;
      }
      const response = await fetch(editing ? `/api/admin/service-opening-windows/${editing.id}` : "/api/admin/service-opening-windows", {
        method: editing ? "PUT" : "POST",
        headers: { Accept: "application/json", "Content-Type": "application/json", Authorization: `Bearer ${token}` },
        body: JSON.stringify(payload),
      });
      const result = await response.json().catch(() => ({}));
      if (response.status === 401) {
        sessionStorage.removeItem("jwt_token");
        router.replace("/cradt-login");
        return;
      }
      if (!response.ok) {
        const errors = result.errors as Record<string, string[]> | undefined;
        if (errors) setFieldErrors(Object.fromEntries(Object.entries(errors).map(([key, values]) => [key, values[0]])));
        throw new Error(errorMessage(result, "Não foi possível salvar a janela."));
      }
      const successNotice = editing ? "Janela atualizada." : form.status === "published" ? "Janela publicada." : "Rascunho salvo.";
      resetForm();
      setNotice(successNotice);
      await loadData();
    } catch (reason) {
      setFormError(reason instanceof Error ? reason.message : "Não foi possível salvar a janela.");
    } finally {
      setSaving(false);
    }
  };

  const serviceName = (id: string) => services.find((service) => service.id === id)?.name ?? "Serviço indisponível";
  const campusName = (id: string | null) => id ? (campuses.find((campus) => campus.id === id)?.name ?? `Campus indisponível (${id.slice(0, 8)})`) : "Todos os campi";
  const courseName = (id: string | null) => id ? (courses.find((course) => course.id === id)?.name ?? `Curso indisponível (${id.slice(0, 8)})`) : "Todos os cursos";
  const counts = {
    published: windows.filter((window) => window.status === "published").length,
    draft: windows.filter((window) => window.status === "draft").length,
    inactive: windows.filter((window) => window.status === "inactive").length,
  };

  return (
    <main className="min-h-screen bg-slate-50 px-4 py-6 text-slate-900 sm:px-6 lg:px-8">
      <div className="mx-auto max-w-7xl space-y-6">
        <header className="space-y-4">
          <Link href="/dashboard/admin" className="inline-flex min-h-11 items-center gap-2 rounded-lg text-sm font-semibold text-emerald-900 hover:text-emerald-700 focus-visible:outline-offset-4">
            <ArrowLeft aria-hidden="true" size={18} /> Voltar ao painel
          </Link>
          <div className="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <p className="text-xs font-bold uppercase tracking-[0.16em] text-emerald-800">Administração de serviços</p>
              <h1 className="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Calendário de atendimento</h1>
              <p className="mt-2 max-w-3xl text-base leading-6 text-slate-600">Configure quando cada serviço pode receber requerimentos e em quais campi ou cursos a janela se aplica.</p>
            </div>
            <div className="flex flex-wrap gap-2" aria-label="Resumo das janelas">
              {(["published", "draft", "inactive"] as WindowStatus[]).map((status) => (
                <span key={status} className={`inline-flex min-h-9 items-center gap-2 rounded-full border px-3 text-sm font-semibold ${STATUS_STYLE[status]}`}>
                  <span>{counts[status]}</span> {STATUS_LABEL[status]}
                </span>
              ))}
            </div>
          </div>
        </header>

        {pageError && (
          <section role="alert" className="flex flex-col gap-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-950 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 className="font-bold">Não foi possível abrir o calendário</h2><p className="mt-1 text-sm leading-5">{pageError}</p></div>
            <button type="button" onClick={() => void loadData()} className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg border border-rose-300 bg-white px-4 text-sm font-semibold hover:bg-rose-100">
              <RotateCw size={16} aria-hidden="true" /> Tentar novamente
            </button>
          </section>
        )}

        {loading ? (
          <div role="status" aria-live="polite" className="space-y-4" aria-busy="true">
            <span className="sr-only">Carregando calendário de atendimento…</span>
            <div className="h-64 animate-pulse rounded-2xl border border-slate-200 bg-white" />
            <div className="h-44 animate-pulse rounded-2xl border border-slate-200 bg-white" />
          </div>
        ) : !pageError && (
          <>
            <section aria-labelledby="calendar-form-title" className="rounded-2xl border border-slate-200 bg-white shadow-sm">
              <div className="flex items-start gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <span className="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-900"><CalendarDays size={20} aria-hidden="true" /></span>
                <div>
                  <h2 id="calendar-form-title" tabIndex={-1} className="text-lg font-bold text-slate-950">{editing ? "Editar janela" : "Nova janela de atendimento"}</h2>
                  <p className="mt-1 text-sm leading-5 text-slate-600">Salve primeiro como rascunho. Publicações exigem uma referência institucional.</p>
                </div>
              </div>

              <form onSubmit={submit} noValidate className="space-y-5 p-5 sm:p-6">
                {formError && <p role="alert" className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-950">{formError}</p>}
                {notice && <p role="status" className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-950">{notice}</p>}

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                  <label className="space-y-1.5 text-sm font-semibold md:col-span-2 xl:col-span-2">
                    <span>Serviço <span className="text-rose-700">*</span></span>
                    <select required value={form.type_request_id} onChange={(event) => setForm({ ...form, type_request_id: event.target.value })} aria-invalid={Boolean(fieldErrors.type_request_id)} aria-describedby={fieldErrors.type_request_id ? "service-error" : undefined} className="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base font-normal focus-visible:outline-emerald-800">
                      <option value="">Selecione um serviço</option>
                      {services.map((service) => <option key={service.id} value={service.id}>{service.name}</option>)}
                    </select>
                    {fieldErrors.type_request_id && <span id="service-error" className="block text-sm font-medium text-rose-800">{fieldErrors.type_request_id}</span>}
                  </label>

                  <label className="space-y-1.5 text-sm font-semibold">
                    <span>Campus</span>
                    <select value={form.campus_id} onChange={(event) => setForm({ ...form, campus_id: event.target.value, course_id: "" })} className="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base font-normal">
                      <option value="">Todos os campi</option>
                      {campuses.filter((campus) => campus.is_active !== false).map((campus) => <option key={campus.id} value={campus.id}>{campus.name} · {campus.code}</option>)}
                    </select>
                  </label>

                  <label className="space-y-1.5 text-sm font-semibold">
                    <span>Curso</span>
                    <select value={form.course_id} onChange={(event) => {
                      const course = courses.find((item) => item.id === event.target.value);
                      setForm({ ...form, course_id: event.target.value, campus_id: course?.campus_id ?? form.campus_id });
                    }} className="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base font-normal">
                      <option value="">Todos os cursos</option>
                      {formCourses.filter((course) => course.is_active !== false).map((course) => <option key={course.id} value={course.id}>{course.name} · {course.code}</option>)}
                    </select>
                    {fieldErrors.course_id && <span className="block text-sm font-medium text-rose-800">{fieldErrors.course_id}</span>}
                  </label>
                </div>

                <div className="grid gap-4 border-t border-slate-100 pt-5 md:grid-cols-2 xl:grid-cols-4">
                  <label className="space-y-1.5 text-sm font-semibold">
                    <span>Início <span className="text-rose-700">*</span></span>
                    <input required type="datetime-local" value={form.starts_at} onChange={(event) => setForm({ ...form, starts_at: event.target.value })} aria-invalid={Boolean(fieldErrors.starts_at)} aria-describedby={fieldErrors.starts_at ? "start-error" : "timezone-help"} className="box-border min-h-11 min-w-0 w-full max-w-full rounded-lg border border-slate-300 bg-white px-2 text-sm font-normal sm:px-3 sm:text-base" />
                    {fieldErrors.starts_at && <span id="start-error" className="block text-sm font-medium text-rose-800">{fieldErrors.starts_at}</span>}
                  </label>
                  <label className="space-y-1.5 text-sm font-semibold">
                    <span>Encerramento <span className="text-rose-700">*</span></span>
                    <input required type="datetime-local" value={form.ends_at} onChange={(event) => setForm({ ...form, ends_at: event.target.value })} aria-invalid={Boolean(fieldErrors.ends_at)} aria-describedby={fieldErrors.ends_at ? "end-error" : "timezone-help"} className="box-border min-h-11 min-w-0 w-full max-w-full rounded-lg border border-slate-300 bg-white px-2 text-sm font-normal sm:px-3 sm:text-base" />
                    {fieldErrors.ends_at && <span id="end-error" className="block text-sm font-medium text-rose-800">{fieldErrors.ends_at}</span>}
                  </label>
                  <label className="space-y-1.5 text-sm font-semibold">
                    <span>Fuso horário <span className="text-rose-700">*</span></span>
                    <select value={form.timezone} onChange={(event) => setForm({ ...form, timezone: event.target.value })} className="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base font-normal">
                      {TIMEZONES.map((timezone) => <option key={timezone} value={timezone}>{timezone}</option>)}
                    </select>
                  </label>
                  <label className="space-y-1.5 text-sm font-semibold">
                    <span>Status</span>
                    <select value={form.status} onChange={(event) => setForm({ ...form, status: event.target.value as WindowStatus })} className="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base font-normal">
                      <option value="draft">Rascunho</option>
                      <option value="published">Publicar</option>
                      <option value="inactive">Desativada</option>
                    </select>
                  </label>
                  <p id="timezone-help" className="flex items-start gap-2 text-sm leading-5 text-slate-600 md:col-span-2 xl:col-span-4">
                    <Clock3 size={17} className="mt-0.5 shrink-0 text-emerald-800" aria-hidden="true" />
                    <span>As datas acima são interpretadas em <strong className="font-semibold text-slate-800">{form.timezone}</strong> e gravadas em UTC. Horários inexistentes ou repetidos por mudança de fuso serão recusados.</span>
                  </p>
                </div>

                {form.status === "published" && (
                  <label className="block max-w-3xl space-y-1.5 text-sm font-semibold">
                    <span>Referência institucional <span className="text-rose-700">*</span></span>
                    <input required value={form.source_reference} onChange={(event) => setForm({ ...form, source_reference: event.target.value })} maxLength={255} aria-invalid={Boolean(fieldErrors.source_reference)} aria-describedby={fieldErrors.source_reference ? "reference-error" : "reference-help"} placeholder="Ex.: ato, portaria ou calendário aprovado" className="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-base font-normal" />
                    <span id="reference-help" className="block font-normal leading-5 text-slate-600">Informe a referência aprovada pela instituição. A tela não cria datas nem referências oficiais.</span>
                    {fieldErrors.source_reference && <span id="reference-error" className="block font-medium text-rose-800">{fieldErrors.source_reference}</span>}
                  </label>
                )}

                <div className="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                  <p className="flex items-center gap-2 text-sm text-slate-600"><ShieldCheck size={17} className="shrink-0 text-emerald-800" aria-hidden="true" /> Sobreposições publicadas no mesmo serviço e escopo são bloqueadas.</p>
                  <div className="flex flex-col-reverse gap-2 sm:flex-row">
                    {editing && <button type="button" onClick={resetForm} className="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-800 hover:bg-slate-50">Cancelar edição</button>}
                    <button type="submit" disabled={saving} className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-emerald-900 px-5 text-sm font-bold text-white transition-colors hover:bg-emerald-800 disabled:cursor-wait disabled:opacity-60">
                      {saving ? <LoaderCircle className="animate-spin" size={17} aria-hidden="true" /> : editing ? <Save size={17} aria-hidden="true" /> : form.status === "published" ? <Check size={17} aria-hidden="true" /> : <Plus size={17} aria-hidden="true" />}
                      {saving ? "Salvando…" : form.status === "published" ? (editing ? "Salvar publicação" : "Publicar janela") : form.status === "inactive" ? "Desativar janela" : (editing ? "Salvar rascunho" : "Salvar rascunho")}
                    </button>
                  </div>
                </div>
              </form>
            </section>

            <section aria-labelledby="window-list-title" className="space-y-4">
              <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                  <h2 id="window-list-title" className="text-xl font-bold tracking-tight text-slate-950">Janelas configuradas</h2>
                  <p className="mt-1 text-sm text-slate-600">{visibleWindows.length} de {windows.length} janelas</p>
                </div>
              </div>

              <div className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-4">
                <label className="space-y-1 text-sm font-semibold"><span>Filtrar serviço</span><select value={filterService} onChange={(event) => setFilterService(event.target.value)} className="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 font-normal"><option value="">Todos os serviços</option>{services.map((service) => <option key={service.id} value={service.id}>{service.name}</option>)}</select></label>
                <label className="space-y-1 text-sm font-semibold"><span>Filtrar campus</span><select value={filterCampus} onChange={(event) => { setFilterCampus(event.target.value); setFilterCourse(""); }} className="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 font-normal"><option value="">Todos os campi</option>{campuses.map((campus) => <option key={campus.id} value={campus.id}>{campus.name}</option>)}</select></label>
                <label className="space-y-1 text-sm font-semibold"><span>Filtrar curso</span><select value={filterCourse} onChange={(event) => setFilterCourse(event.target.value)} className="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 font-normal"><option value="">Todos os cursos</option>{filterCourses.map((course) => <option key={course.id} value={course.id}>{course.name}</option>)}</select></label>
                <label className="space-y-1 text-sm font-semibold"><span>Filtrar status</span><select value={filterStatus} onChange={(event) => setFilterStatus(event.target.value as WindowStatus | "")} className="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 font-normal"><option value="">Todos os status</option>{(["published", "draft", "inactive"] as WindowStatus[]).map((status) => <option key={status} value={status}>{STATUS_LABEL[status]}</option>)}</select></label>
              </div>

              {visibleWindows.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
                  <span className="mx-auto flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-600"><CalendarDays size={22} aria-hidden="true" /></span>
                  <h3 className="mt-4 text-base font-bold text-slate-900">{windows.length === 0 ? "Nenhuma janela cadastrada" : "Nenhuma janela corresponde aos filtros"}</h3>
                  <p className="mx-auto mt-2 max-w-lg text-sm leading-5 text-slate-600">{windows.length === 0 ? "Crie um rascunho para organizar o calendário. A disponibilidade só muda quando uma janela é publicada com referência aprovada." : "Altere ou limpe os filtros para consultar outras janelas."}</p>
                  {windows.length > 0 && <button type="button" onClick={() => { setFilterService(""); setFilterCampus(""); setFilterCourse(""); setFilterStatus(""); }} className="mt-4 min-h-11 rounded-lg px-4 text-sm font-semibold text-emerald-900 underline underline-offset-4">Limpar filtros</button>}
                </div>
              ) : (
                <ul className="space-y-3">
                  {visibleWindows.map((window) => (
                    <li key={window.id}>
                      <article className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-shadow hover:shadow-md sm:p-5">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                          <div className="min-w-0 space-y-3">
                            <div className="flex flex-wrap items-center gap-2">
                              <h3 className="text-base font-bold text-slate-950">{serviceName(window.type_request_id)}</h3>
                              <span className={`inline-flex min-h-7 items-center rounded-full border px-2.5 text-xs font-bold ${STATUS_STYLE[window.status]}`}>{STATUS_LABEL[window.status]}</span>
                            </div>
                            <p className="text-sm text-slate-700">{campusName(window.campus_id)} <span className="px-1 text-slate-400">·</span> {courseName(window.course_id)}</p>
                            <div className="grid gap-1 text-sm text-slate-600 sm:grid-cols-2">
                              <p><span className="font-semibold text-slate-800">Início:</span> {formatWindowTime(window.starts_at, window.timezone)}</p>
                              <p><span className="font-semibold text-slate-800">Fim:</span> {formatWindowTime(window.ends_at, window.timezone)}</p>
                            </div>
                            {window.status === "published" && <p className="text-sm text-slate-600"><span className="font-semibold text-slate-800">Referência:</span> {window.source_reference || "Não informada"}</p>}
                          </div>
                          <button type="button" onClick={() => editWindow(window)} disabled={!window.timestamps_utc_normalized} aria-label={`Editar janela ${serviceName(window.type_request_id)}`} title={window.timestamps_utc_normalized ? undefined : "Edição bloqueada: confirme o significado do horário deste registro legado antes de alterá-lo."} className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-800 hover:border-emerald-700 hover:bg-emerald-50 hover:text-emerald-950 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
                            <Pencil size={16} aria-hidden="true" /> {window.timestamps_utc_normalized ? "Editar janela" : "Edição bloqueada"}
                          </button>
                        </div>
                        {!window.timestamps_utc_normalized && <p className="mt-3 text-sm text-amber-900">Este registro não tem confirmação de fuso UTC. Confirme o fuso histórico antes de alterar suas datas.</p>}
                      </article>
                    </li>
                  ))}
                </ul>
              )}
            </section>
          </>
        )}
      </div>
    </main>
  );
}

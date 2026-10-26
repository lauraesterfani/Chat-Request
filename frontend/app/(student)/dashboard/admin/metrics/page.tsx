"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import axios from "axios";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { AlertCircle, ArrowLeft, BarChart3, Download, FileSpreadsheet, FileText, Loader2, RefreshCw, Star } from "lucide-react";

const API_BASE = "/api";
const STATUS_LABELS: Record<string, string> = {
  pending: "Recebido", analyzing: "Em análise", waiting: "Aguardando aluno",
  solving: "Em execução", completed: "Concluído", canceled: "Cancelado",
};
const SECTORS = [{ value: "CRADT", label: "CRADT" }, { value: "COORDENACAO", label: "Coordenação" }];

type MetricsFilters = { from: string; to: string; course_id: string; campus_id: string; type_id: string; sector: string; status: string };
type DistributionRow = { id?: string | null; value?: string | null; label: string; count: number };
type MetricsData = {
  total: number; open: number; completed: number; canceled?: number; cancelled?: number;
  by_status: Record<string, number>;
  distribution: { cohort: string; by_service: DistributionRow[]; by_campus: DistributionRow[]; by_course: DistributionRow[]; by_sector: DistributionRow[] };
  cohorts: {
    opened_in_period: { count: number; period_field: string };
    resolved_in_period: { count: number; period_field: string; by_status: Record<string, number>; legacy_terminal_records_without_timestamp: number };
    current_stock: { count: number; by_status: Record<string, number> };
  };
  first_response_coverage: { requests_with_first_human_response: number; requests_without_first_human_response: number; total_requests: number };
  satisfaction: { responses: number; total_responses?: number; eligible_closed_requests: number; response_rate_percent: number | null; average_rating: number | null; by_rating: Record<string, number> };
  generated_at: string; timezone: string; limitations: string[];
};
type Option = { id: string; name: string };
type FilterOptions = { campuses: Option[]; courses: Option[]; services: Option[]; sectors: { value: string; label: string }[] };
const EMPTY_FILTERS: MetricsFilters = { from: "", to: "", course_id: "", campus_id: "", type_id: "", sector: "", status: "" };

function tokenOrRedirect(router: ReturnType<typeof useRouter>) {
  const token = sessionStorage.getItem("jwt_token");
  if (!token) router.push("/cradt-login");
  return token;
}

function filtersForRequest(filters: MetricsFilters) {
  return Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== ""));
}

function DistributionTable({ title, caption, rows }: { title: string; caption: string; rows: DistributionRow[] }) {
  const orderedRows = [...rows].sort((left, right) => right.count - left.count || left.label.localeCompare(right.label, "pt-BR"));

  return (
    <article className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
      <h2 className="mb-4 text-lg font-bold text-gray-900">{title}</h2>
      {orderedRows.length === 0 ? <p className="text-sm text-gray-600">Não há dados nesta distribuição.</p> : <div className="overflow-x-auto">
        <table className="w-full border-collapse text-left text-sm">
          <caption className="sr-only">{caption}</caption>
          <thead><tr className="border-b border-gray-200 text-gray-700"><th scope="col" className="py-2 pr-4">{title.replace("Requerimentos por ", "")}</th><th scope="col" className="py-2 text-right">Quantidade</th></tr></thead>
          <tbody>{orderedRows.map((row) => <tr key={`${row.id ?? row.value ?? "unassigned"}-${row.label}`} className="border-b border-gray-100 last:border-0"><th scope="row" className="py-3 pr-4 font-medium text-gray-800">{row.label}</th><td className="py-3 text-right tabular-nums text-gray-900">{row.count}</td></tr>)}</tbody>
        </table>
      </div>}
    </article>
  );
}

export default function MetricsPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [exporting, setExporting] = useState<"xlsx" | "pdf" | null>(null);
  const [metrics, setMetrics] = useState<MetricsData | null>(null);
  const [filterOptions, setFilterOptions] = useState<FilterOptions>({ campuses: [], courses: [], services: [], sectors: SECTORS });
  const [filters, setFilters] = useState<MetricsFilters>(EMPTY_FILTERS);
  const [error, setError] = useState("");
  const [exportError, setExportError] = useState("");
  const [notice, setNotice] = useState("");

  useEffect(() => {
    const loadOptions = async () => {
      try {
        const token = sessionStorage.getItem("jwt_token");
        if (!token) return;
        const response = await axios.get<FilterOptions>(`${API_BASE}/metrics/filters`, { headers: { Authorization: `Bearer ${token}` } });
        setFilterOptions({
          campuses: response.data.campuses ?? [], courses: response.data.courses ?? [],
          services: response.data.services ?? [], sectors: response.data.sectors ?? SECTORS,
        });
      } catch {
        // The free-text campus field remains available if filter options cannot load.
      }
    };
    void loadOptions();
  }, []);

  const fetchMetrics = useCallback(async () => {
    setLoading(true); setError(""); setNotice("");
    try {
      const token = tokenOrRedirect(router);
      if (!token) return;
      const response = await axios.get<MetricsData>(`${API_BASE}/metrics`, {
        headers: { Authorization: `Bearer ${token}` }, params: filtersForRequest(filters),
      });
      setMetrics(response.data);
      setNotice(response.data.total === 0 ? "Nenhum requerimento corresponde a estes filtros." : "");
    } catch (requestError) {
      if (axios.isAxiosError(requestError) && requestError.response?.status === 401) {
        sessionStorage.removeItem("jwt_token"); router.push("/cradt-login");
      } else if (axios.isAxiosError(requestError) && requestError.response?.status === 422) {
        setError("Revise os filtros: o período final deve ser igual ou posterior ao inicial e os identificadores precisam ser válidos.");
      } else setError("Não foi possível carregar os indicadores. Tente novamente.");
    } finally { setLoading(false); }
  }, [filters, router]);

  useEffect(() => {
    const timer = window.setTimeout(() => { void fetchMetrics(); }, 0);
    return () => window.clearTimeout(timer);
  }, [fetchMetrics]);

  const update = (key: keyof MetricsFilters, value: string) => setFilters((current) => ({ ...current, [key]: value }));
  const statusRows = useMemo(() => Object.entries(STATUS_LABELS).map(([key, label]) => ({ key, label, count: metrics?.by_status?.[key] ?? 0 })), [metrics]);
  const stockRows = useMemo(() => Object.entries(STATUS_LABELS).map(([key, label]) => ({ key, label, count: metrics?.cohorts?.current_stock?.by_status?.[key] ?? 0 })), [metrics]);

  const downloadExport = async (format: "xlsx" | "pdf") => {
    setExporting(format); setExportError(""); setNotice("");
    try {
      const token = tokenOrRedirect(router);
      if (!token) return;
      const headers = { Authorization: `Bearer ${token}` };
      const requested = await axios.post<{ download_path: string }>(`${API_BASE}/metrics/exports/${format}`, filtersForRequest(filters), { headers });
      const downloadPath = requested.data.download_path;
      if (!downloadPath || !downloadPath.startsWith("/api/metrics/exports/")) throw new Error("Caminho de download inválido.");
      const file = await axios.get<Blob>(downloadPath, { headers, responseType: "blob" });
      const blobUrl = URL.createObjectURL(file.data);
      const anchor = document.createElement("a");
      anchor.href = blobUrl;
      anchor.download = `indicadores-${new Date().toISOString().slice(0, 10)}.${format}`;
      document.body.appendChild(anchor); anchor.click(); anchor.remove(); window.setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
      setNotice(`Relatório ${format.toUpperCase()} baixado. O arquivo é agregado e não inclui dados pessoais.`);
    } catch (requestError) {
      if (axios.isAxiosError(requestError) && requestError.response?.status === 401) {
        sessionStorage.removeItem("jwt_token"); router.push("/cradt-login");
      } else setExportError("Não foi possível gerar ou baixar o relatório. Verifique sua permissão e tente novamente.");
    } finally { setExporting(null); }
  };

  return (
    <main className="min-h-screen bg-gray-50 p-4 sm:p-8">
      <div className="mx-auto max-w-7xl">
        <header className="mb-8 flex items-center gap-4">
          <Link href="/dashboard/admin" aria-label="Voltar ao painel administrativo" className="rounded-xl border border-gray-200 p-2 text-gray-600 transition hover:bg-white hover:shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"><ArrowLeft size={20} /></Link>
          <span className="rounded-xl bg-[#004d40] p-2 text-white"><BarChart3 size={22} /></span>
          <div><h1 className="text-2xl font-bold text-gray-900">Métricas</h1><p className="text-sm text-gray-600">Indicadores agregados dos requerimentos autorizados</p></div>
        </header>

        <section aria-labelledby="filters-heading" className="mb-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
          <h2 id="filters-heading" className="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-600">Filtros</h2>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <label className="text-sm font-medium text-gray-700">A partir de (UTC)<input aria-label="Data inicial" type="date" value={filters.from} onChange={(event) => update("from", event.target.value)} className="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 font-normal focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" /></label>
            <label className="text-sm font-medium text-gray-700">Até (UTC)<input aria-label="Data final" type="date" value={filters.to} onChange={(event) => update("to", event.target.value)} className="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 font-normal focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" /></label>
            <label className="text-sm font-medium text-gray-700">Curso<select aria-label="Curso" value={filters.course_id} onChange={(event) => update("course_id", event.target.value)} className="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 font-normal focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"><option value="">Todos os cursos autorizados</option>{filterOptions.courses.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select></label>
            <label className="text-sm font-medium text-gray-700">Campus<select aria-label="Campus" value={filters.campus_id} onChange={(event) => update("campus_id", event.target.value)} className="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 font-normal focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"><option value="">Todos os campi autorizados</option>{filterOptions.campuses.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select></label>
            <label className="text-sm font-medium text-gray-700">Serviço<select aria-label="Serviço" value={filters.type_id} onChange={(event) => update("type_id", event.target.value)} className="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 font-normal focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"><option value="">Todos os serviços</option>{filterOptions.services.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select></label>
            <label className="text-sm font-medium text-gray-700">Setor responsável<select aria-label="Setor responsável" value={filters.sector} onChange={(event) => update("sector", event.target.value)} className="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 font-normal focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"><option value="">Todos os setores</option>{filterOptions.sectors.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</select></label>
            <label className="text-sm font-medium text-gray-700">Etapa<select aria-label="Etapa" value={filters.status} onChange={(event) => update("status", event.target.value)} className="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 font-normal focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"><option value="">Todas as etapas</option>{Object.entries(STATUS_LABELS).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></label>
            <div className="flex flex-wrap items-end gap-2">
              <button type="button" onClick={() => void fetchMetrics()} disabled={loading} className="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-[#004d40] px-4 text-sm font-semibold text-white transition hover:bg-[#00695c] disabled:cursor-wait disabled:opacity-60">{loading ? <Loader2 size={16} className="animate-spin" /> : <RefreshCw size={16} />}Atualizar</button>
              <button type="button" onClick={() => setFilters(EMPTY_FILTERS)} disabled={loading} className="min-h-11 rounded-xl border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Limpar</button>
            </div>
          </div>
          <p className="mt-3 text-xs leading-5 text-gray-600">Datas usam dias completos em UTC e consideram a abertura do requerimento. As opções são limitadas aos requerimentos visíveis para sua conta.</p>
        </section>

        {error && <div role="alert" className="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><AlertCircle size={18} aria-hidden="true" /><p>{error}</p><button type="button" onClick={() => void fetchMetrics()} className="ml-auto font-semibold underline">Tentar novamente</button></div>}
        {exportError && <div role="alert" className="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{exportError}</div>}
        {notice && <div role="status" className="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">{notice}</div>}

        {loading ? <div role="status" aria-live="polite" className="flex items-center justify-center gap-3 py-24 text-gray-700"><Loader2 size={26} className="animate-spin text-emerald-800" /><span>Carregando métricas…</span></div> : metrics ? <>
          {metrics.total === 0 ? <div className="mb-5 rounded-xl border border-gray-200 bg-white p-5 text-gray-700">Nenhum dado para exibir com os filtros selecionados.</div> : <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            {[{ label: "Requerimentos no período", value: metrics.total }, { label: "Abertos", value: metrics.open }, { label: "Concluídos", value: metrics.completed }, { label: "Cancelados", value: metrics.canceled ?? metrics.cancelled ?? 0 }].map((card) => <article key={card.label} className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><h2 className="text-sm font-medium text-gray-600">{card.label}</h2><p className="mt-1 text-3xl font-bold text-slate-900">{card.value}</p></article>)}
          </div>}

          <div className="mb-6 grid gap-6 lg:grid-cols-2">
            <section aria-labelledby="status-heading" className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
              <h2 id="status-heading" className="mb-4 text-lg font-bold text-gray-900">Etapas dos pedidos abertos no período</h2>
              <div className="overflow-x-auto"><table className="w-full border-collapse text-left text-sm"><caption className="sr-only">Contagem por etapa de requerimentos cuja abertura ocorreu no período selecionado</caption><thead><tr className="border-b border-gray-200 text-gray-700"><th scope="col" className="py-2 pr-4">Etapa</th><th scope="col" className="py-2 text-right">Quantidade</th></tr></thead><tbody>{statusRows.map((row) => <tr key={row.key} className="border-b border-gray-100 last:border-0"><th scope="row" className="py-3 pr-4 font-medium text-gray-800">{row.label}</th><td className="py-3 text-right tabular-nums text-gray-900">{row.count}</td></tr>)}</tbody></table></div>
            </section>
            <section aria-labelledby="satisfaction-heading" className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
              <div className="mb-4 flex items-center gap-3"><span className="rounded-xl bg-amber-50 p-2"><Star size={20} className="text-amber-700" aria-hidden="true" /></span><h2 id="satisfaction-heading" className="text-lg font-bold text-gray-900">Satisfação</h2></div>
              <dl className="mb-4 grid grid-cols-2 gap-3 text-sm"><div className="rounded-xl bg-gray-50 p-3"><dt className="text-gray-700">Respostas recebidas</dt><dd className="mt-1 text-2xl font-bold text-gray-900">{metrics.satisfaction.responses ?? metrics.satisfaction.total_responses ?? 0}</dd></div><div className="rounded-xl bg-amber-50 p-3"><dt className="text-amber-900">Média (de 5)</dt><dd className="mt-1 text-2xl font-bold text-amber-900">{metrics.satisfaction.average_rating == null ? "—" : metrics.satisfaction.average_rating.toFixed(2)}</dd></div><div className="rounded-xl bg-gray-50 p-3"><dt className="text-gray-700">Elegíveis encerrados</dt><dd className="mt-1 text-2xl font-bold text-gray-900">{metrics.satisfaction.eligible_closed_requests}</dd></div><div className="rounded-xl bg-gray-50 p-3"><dt className="text-gray-700">Taxa de resposta</dt><dd className="mt-1 text-2xl font-bold text-gray-900">{metrics.satisfaction.response_rate_percent == null ? "—" : `${metrics.satisfaction.response_rate_percent.toFixed(2)}%`}</dd></div></dl>
              <div className="overflow-x-auto"><table className="w-full border-collapse text-left text-sm"><caption className="sr-only">Distribuição das respostas de satisfação por nota</caption><thead><tr className="border-b border-gray-200 text-gray-700"><th scope="col" className="py-2 pr-4">Nota</th><th scope="col" className="py-2 text-right">Respostas</th></tr></thead><tbody>{[5, 4, 3, 2, 1].map((rating) => <tr key={rating} className="border-b border-gray-100 last:border-0"><th scope="row" className="py-2 pr-4 font-medium text-gray-800">{rating} de 5</th><td className="py-2 text-right tabular-nums text-gray-900">{metrics.satisfaction.by_rating?.[String(rating)] ?? 0}</td></tr>)}</tbody></table></div>
            </section>
          </div>

          <section aria-label="Coortes de abertura, conclusão e estoque atual" className="mb-6 grid gap-6 lg:grid-cols-3">
            <article className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><h2 className="text-sm font-medium text-gray-600">Abertos no período</h2><p className="mt-1 text-3xl font-bold text-slate-900">{metrics.cohorts?.opened_in_period?.count ?? metrics.total}</p><p className="mt-2 text-xs leading-5 text-gray-600">Usa a data de criação. Esta coorte não é dividida pela quantidade concluída em outro período.</p></article>
            <article className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><h2 className="text-sm font-medium text-gray-600">Concluídos ou cancelados no período</h2><p className="mt-1 text-3xl font-bold text-slate-900">{metrics.cohorts?.resolved_in_period?.count ?? 0}</p><p className="mt-2 text-xs leading-5 text-gray-600">Usa `resolved_at`; registros legados sem essa data ficam fora e são contados no diagnóstico.</p></article>
            <article className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><h2 className="text-sm font-medium text-gray-600">Estoque atual autorizado</h2><p className="mt-1 text-3xl font-bold text-slate-900">{metrics.cohorts?.current_stock?.count ?? 0}</p><p className="mt-2 text-xs leading-5 text-gray-600">Contagem atual no escopo e nos filtros organizacionais selecionados; não usa o intervalo de datas.</p></article>
          </section>

          <section aria-label="Etapas do estoque e resultados no período de conclusão" className="mb-6 grid gap-6 lg:grid-cols-2">
            <section aria-labelledby="stock-heading" className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><h2 id="stock-heading" className="mb-4 text-lg font-bold text-gray-900">Estoque atual por etapa</h2><div className="overflow-x-auto"><table className="w-full border-collapse text-left text-sm"><caption className="sr-only">Contagem do estoque atual em cada etapa operacional</caption><thead><tr className="border-b border-gray-200 text-gray-700"><th scope="col" className="py-2 pr-4">Etapa</th><th scope="col" className="py-2 text-right">Quantidade</th></tr></thead><tbody>{stockRows.map((row) => <tr key={row.key} className="border-b border-gray-100 last:border-0"><th scope="row" className="py-3 pr-4 font-medium text-gray-800">{row.label}</th><td className="py-3 text-right tabular-nums text-gray-900">{row.count}</td></tr>)}</tbody></table></div></section>
            <section aria-labelledby="resolved-heading" className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><h2 id="resolved-heading" className="mb-4 text-lg font-bold text-gray-900">Encerramentos por etapa no período</h2><div className="overflow-x-auto"><table className="w-full border-collapse text-left text-sm"><caption className="sr-only">Conclusões e cancelamentos dentro do período, baseados em resolved_at</caption><thead><tr className="border-b border-gray-200 text-gray-700"><th scope="col" className="py-2 pr-4">Resultado operacional</th><th scope="col" className="py-2 text-right">Quantidade</th></tr></thead><tbody>{["completed", "canceled"].map((status) => <tr key={status} className="border-b border-gray-100 last:border-0"><th scope="row" className="py-3 pr-4 font-medium text-gray-800">{STATUS_LABELS[status]}</th><td className="py-3 text-right tabular-nums text-gray-900">{metrics.cohorts?.resolved_in_period?.by_status?.[status] ?? 0}</td></tr>)}</tbody></table></div><p className="mt-3 text-xs leading-5 text-gray-600">Registros encerrados sem data de encerramento: {metrics.cohorts?.resolved_in_period?.legacy_terminal_records_without_timestamp ?? 0}.</p></section>
          </section>

          <section aria-label="Distribuição dos requerimentos no período" className="mb-6 grid gap-6 lg:grid-cols-2">
            <DistributionTable title="Requerimentos por serviço" caption="Requerimentos abertos por serviço no período selecionado" rows={metrics.distribution?.by_service ?? []} />
            <DistributionTable title="Requerimentos por campus" caption="Requerimentos abertos por campus no período selecionado" rows={metrics.distribution?.by_campus ?? []} />
            <DistributionTable title="Requerimentos por curso" caption="Requerimentos abertos por curso no período selecionado" rows={metrics.distribution?.by_course ?? []} />
            <DistributionTable title="Requerimentos por setor" caption="Requerimentos abertos por setor responsável no período selecionado" rows={metrics.distribution?.by_sector ?? []} />
          </section>

          <section aria-labelledby="coverage-heading" className="mb-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><h2 id="coverage-heading" className="mb-2 text-lg font-bold text-gray-900">Cobertura de primeira resposta</h2><p className="text-sm text-gray-700">{metrics.first_response_coverage.requests_with_first_human_response} de {metrics.first_response_coverage.total_requests} requerimentos têm primeira resposta humana registrada. Este indicador conta registros existentes; não mede duração nem cumprimento de SLA.</p></section>

          <section aria-labelledby="exports-heading" className="mb-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 id="exports-heading" className="text-lg font-bold text-gray-900">Exportar indicadores</h2><p className="mt-1 text-sm text-gray-700">Arquivos agregados protegidos, disponíveis por tempo limitado e sem dados pessoais.</p></div><div className="flex flex-wrap gap-3"><button type="button" onClick={() => void downloadExport("xlsx")} disabled={exporting !== null} className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-emerald-800 px-4 text-sm font-semibold text-emerald-900 hover:bg-emerald-50 disabled:opacity-60">{exporting === "xlsx" ? <Loader2 size={17} className="animate-spin" /> : <FileSpreadsheet size={17} />}Baixar XLSX</button><button type="button" onClick={() => void downloadExport("pdf")} disabled={exporting !== null} className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#004d40] px-4 text-sm font-semibold text-white hover:bg-[#00695c] disabled:opacity-60">{exporting === "pdf" ? <Loader2 size={17} className="animate-spin" /> : <FileText size={17} />}Baixar PDF<Download size={15} aria-hidden="true" /></button></div></div></section>

          <details className="rounded-2xl border border-gray-200 bg-white p-5"><summary className="cursor-pointer text-sm font-semibold text-gray-800">Definições e limitações dos indicadores</summary><ul className="mt-3 list-disc space-y-2 pl-5 text-sm leading-6 text-gray-700">{metrics.limitations.map((limitation) => <li key={limitation}>{limitation}</li>)}<li>Os resultados são agregados dentro do escopo de acesso atual do atendente; não exibem identificação ou conteúdo dos requerimentos.</li><li>A participação na pesquisa de satisfação é voluntária e pode refletir viés de resposta. Confirme a elegibilidade institucional descrita pela API.</li></ul><p className="mt-4 text-xs text-gray-600">Gerado em {new Date(metrics.generated_at).toLocaleString("pt-BR", { timeZone: metrics.timezone || "UTC" })} ({metrics.timezone || "UTC"}).</p></details>
        </> : !error ? <div className="rounded-xl border border-gray-200 bg-white p-6 text-gray-700">Ainda não há dados disponíveis.</div> : null}
      </div>
    </main>
  );
}

import { expect, test } from "@playwright/test";
import AxeBuilder from "@axe-core/playwright";

const metricData = {
  total: 4, open: 2, completed: 1, canceled: 1, cancelled: 1,
  by_status: { pending: 1, analyzing: 1, waiting: 0, solving: 0, completed: 1, canceled: 1 },
  distribution: {
    cohort: "requests_opened_in_selected_period",
    by_service: [{ id: "type-uuid", label: "Serviço de QA", count: 4 }],
    by_campus: [{ id: "campus-uuid", label: "Campus de QA", count: 4 }],
    by_course: [{ id: "course-uuid", label: "Curso de QA", count: 4 }],
    by_sector: [{ value: "CRADT", label: "CRADT", count: 4 }],
  },
  cohorts: {
    opened_in_period: { count: 4, period_field: "created_at" },
    resolved_in_period: { count: 2, period_field: "resolved_at", by_status: { completed: 1, canceled: 1 }, legacy_terminal_records_without_timestamp: 0 },
    current_stock: { count: 8, by_status: { pending: 2, analyzing: 1, waiting: 1, solving: 1, completed: 2, canceled: 1 } },
  },
  first_response_coverage: { requests_with_first_human_response: 3, requests_without_first_human_response: 1, total_requests: 4 },
  satisfaction: { responses: 1, total_responses: 1, eligible_closed_requests: 2, response_rate_percent: 50, average_rating: 4, by_rating: { 1: 0, 2: 0, 3: 0, 4: 1, 5: 0 } },
  generated_at: "2026-10-26T12:00:00.000Z", timezone: "UTC", limitations: ["Indicador fictício para validação automatizada."],
};

test("painel de métricas aplica filtros, exibe tabelas e baixa exportação autenticada", async ({ page }) => {
  const errors: string[] = [];
  const failures: string[] = [];
  const calls: Array<{ method: string; url: string; body?: Record<string, unknown> }> = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });
  page.on("requestfailed", (request) => failures.push(`${request.method()} ${request.url()}`));
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "metrics-admin-token"));
  await page.route("**/api/me", (route) => route.fulfill({ contentType: "application/json", body: JSON.stringify({ id: "qa-admin", name: "Admin QA", role: "admin" }) }));
  await page.route("**/api/notifications**", (route) => route.fulfill({ contentType: "application/json", body: JSON.stringify({ data: [], unread: 0, current_page: 1, last_page: 1, total: 0 }) }));
  await page.route("**/api/metrics/filters", (route) => route.fulfill({ contentType: "application/json", body: JSON.stringify({ campuses: [{ id: "campus-uuid", name: "Campus de QA" }], courses: [{ id: "course-uuid", name: "Curso de QA" }], services: [{ id: "type-uuid", name: "Serviço de QA" }], sectors: [{ value: "CRADT", label: "CRADT" }, { value: "COORDENACAO", label: "Coordenação" }] }) }));
  await page.route("**/api/metrics/exports/{xlsx,pdf}", async (route) => {
    calls.push({ method: route.request().method(), url: new URL(route.request().url()).pathname, body: route.request().postDataJSON() as Record<string, unknown> });
    await route.fulfill({ status: 201, contentType: "application/json", body: JSON.stringify({ download_path: "/api/metrics/exports/export-uuid/download" }) });
  });
  await page.route("**/api/metrics/exports/*/download", async (route) => {
    calls.push({ method: route.request().method(), url: new URL(route.request().url()).pathname });
    await route.fulfill({ status: 200, contentType: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", body: Buffer.from("qa-xlsx") });
  });
  await page.route(/\/api\/metrics(?:\?.*)?$/, async (route) => {
    const request = route.request();
    if (request.method() === "GET") calls.push({ method: "GET", url: new URL(request.url()).pathname, body: Object.fromEntries(new URL(request.url()).searchParams.entries()) });
    await route.fulfill({ contentType: "application/json", body: JSON.stringify(metricData) });
  });

  await page.goto("/dashboard/admin/metrics");
  await expect(page.getByRole("heading", { name: "Métricas" })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Etapas dos pedidos abertos no período" })).toBeVisible();
  await expect(page.getByRole("table", { name: "Contagem por etapa de requerimentos cuja abertura ocorreu no período selecionado" })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Requerimentos por serviço" })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Requerimentos por campus" })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Requerimentos por curso" })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Requerimentos por setor" })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Estoque atual por etapa" })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Encerramentos por etapa no período" })).toBeVisible();
  await expect(page.getByText("Estoque atual autorizado")).toBeVisible();
  await expect(page.getByRole("rowheader", { name: "Serviço de QA" })).toBeVisible();
  await expect(page.getByRole("table", { name: "Distribuição das respostas de satisfação por nota" })).toBeVisible();
  await page.getByLabel("Data inicial").fill("2026-10-01");
  await page.getByLabel("Data final").fill("2026-10-26");
  await page.getByRole("combobox", { name: "Curso" }).selectOption("course-uuid");
  await page.getByRole("combobox", { name: "Campus" }).selectOption("campus-uuid");
  await page.getByRole("combobox", { name: "Serviço" }).selectOption("type-uuid");
  await page.getByRole("combobox", { name: "Setor responsável" }).selectOption("CRADT");
  await page.getByRole("combobox", { name: "Etapa" }).selectOption("pending");
  const selectedFilters = { from: "2026-10-01", to: "2026-10-26", course_id: "course-uuid", campus_id: "campus-uuid", type_id: "type-uuid", sector: "CRADT", status: "pending" };
  await expect.poll(() => calls.some((call) => call.method === "GET" && Object.entries(selectedFilters).every(([key, value]) => call.body?.[key] === value)), { message: `Chamadas GET: ${JSON.stringify(calls)}` }).toBeTruthy();
  await page.getByRole("button", { name: "Baixar XLSX" }).click();
  await expect(page.getByRole("status")).toContainText("Relatório XLSX baixado");
  expect(calls.some((call) => call.method === "POST" && Object.entries(selectedFilters).every(([key, value]) => call.body?.[key] === value))).toBeTruthy();
  expect(calls.some((call) => call.method === "GET" && call.url.endsWith("/download"))).toBeTruthy();
  await page.getByRole("button", { name: "Baixar PDF" }).click();
  await expect(page.getByRole("status")).toContainText("Relatório PDF baixado");
  await page.getByText("Definições e limitações dos indicadores").click();
  await expect(page.getByText("Os resultados são agregados dentro do escopo de acesso atual")).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
  const accessibility = await new AxeBuilder({ page }).withTags(["wcag2a", "wcag2aa", "wcag21aa"]).analyze();
  expect(accessibility.violations, JSON.stringify(accessibility.violations, null, 2)).toEqual([]);
  expect(errors, errors.join("\n")).toEqual([]);
  expect(failures, failures.join("\n")).toEqual([]);
  await page.screenshot({ path: test.info().outputPath("metrics-dashboard.png"), fullPage: true });
});

test("painel mostra estado vazio e permite tentar novamente após falha", async ({ page }) => {
  let shouldFail = true;
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "metrics-admin-token"));
  await page.route("**/api/me", (route) => route.fulfill({ contentType: "application/json", body: JSON.stringify({ id: "qa-admin", name: "Admin QA", role: "admin" }) }));
  await page.route("**/api/notifications**", (route) => route.fulfill({ contentType: "application/json", body: JSON.stringify({ data: [], unread: 0, current_page: 1, last_page: 1, total: 0 }) }));
  await page.route("**/api/metrics/filters", (route) => route.fulfill({ contentType: "application/json", body: JSON.stringify({ campuses: [], courses: [], services: [], sectors: [] }) }));
  await page.route(/\/api\/metrics(?:\?.*)?$/, (route) => {
    if (shouldFail) {
      shouldFail = false;
      return route.fulfill({ status: 500, contentType: "application/json", body: JSON.stringify({ message: "failure" }) });
    }
    return route.fulfill({ contentType: "application/json", body: JSON.stringify({ ...metricData, total: 0, open: 0, completed: 0, canceled: 0, cancelled: 0, by_status: {}, first_response_coverage: { requests_with_first_human_response: 0, requests_without_first_human_response: 0, total_requests: 0 }, satisfaction: { responses: 0, eligible_closed_requests: 0, response_rate_percent: null, average_rating: null, by_rating: {} } }) });
  });

  await page.goto("/dashboard/admin/metrics");
  await expect(page.locator("div[role=alert].mb-5")).toContainText("Não foi possível carregar os indicadores");
  await page.getByRole("button", { name: "Tentar novamente" }).click();
  await expect(page.getByText("Nenhum dado para exibir com os filtros selecionados.")).toBeVisible();
  await expect(page.getByRole("status")).toContainText("Nenhum requerimento corresponde a estes filtros");
});

import AxeBuilder from "@axe-core/playwright";
import { expect, test } from "@playwright/test";

const serviceId = "11111111-1111-4111-8111-111111111111";
const campusId = "22222222-2222-4222-8222-222222222222";
const courseId = "33333333-3333-4333-8333-333333333333";
const windowId = "44444444-4444-4444-8444-444444444444";

const options = {
  campuses: [{ id: campusId, name: "Campus de teste", code: "CT", is_active: true }],
  courses: [{ id: courseId, name: "Curso de teste", code: "CDT", campus_id: campusId, is_active: true }],
};

function draftWindow(overrides: Record<string, unknown> = {}) {
  return {
    id: windowId,
    type_request_id: serviceId,
    campus_id: campusId,
    course_id: courseId,
    starts_at: "2030-12-01T11:00:00.000000Z",
    ends_at: "2030-12-01T12:00:00.000000Z",
    timezone: "America/Recife",
    status: "draft",
    source_reference: null,
    timestamps_utc_normalized: true,
    ...overrides,
  };
}

async function mockCalendar(page: import("@playwright/test").Page, getWindows: () => unknown[] = () => []) {
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "calendar-test-token"));
  await page.route("**/api/me", (route) => route.fulfill({ json: { id: "admin-calendar-qa", name: "Admin de teste", role: "admin" } }));
  await page.route("**/api/admin/type-requests", (route) => route.fulfill({ json: [{ id: serviceId, name: "Serviço acadêmico de teste" }] }));
  await page.route("**/api/staff-access-scopes/options", (route) => route.fulfill({ json: options }));
  await page.route("**/api/admin/service-opening-windows**", (route) => {
    if (route.request().method() === "GET") return route.fulfill({ json: getWindows() });
    return route.fallback();
  });
}

test("admin cria e edita rascunho com datas UTC explícitas no desktop e mobile", async ({ page }) => {
  let saved: Record<string, unknown> | null = null;
  await mockCalendar(page, () => saved ? [saved] : []);
  let submitted: Record<string, unknown> | undefined;
  await page.route("**/api/admin/service-opening-windows**", async (route) => {
    if (route.request().method() !== "POST" && route.request().method() !== "PUT") return route.fallback();
    submitted = route.request().postDataJSON();
    saved = draftWindow(submitted);
    await route.fulfill({ status: route.request().method() === "POST" ? 201 : 200, json: saved });
  });

  await page.goto("/dashboard/admin/calendar");
  await expect(page.getByRole("heading", { name: "Calendário de atendimento" })).toBeVisible();
  await expect(page.getByText("Nenhuma janela cadastrada")).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(await page.evaluate(() => window.innerWidth));
  const accessibility = await new AxeBuilder({ page }).withTags(["wcag2a", "wcag2aa", "wcag21a", "wcag21aa"]).analyze();
  expect(accessibility.violations).toEqual([]);

  await page.getByLabel("Serviço *").selectOption(serviceId);
  await page.getByLabel("Campus").first().selectOption(campusId);
  await page.getByLabel("Curso").first().selectOption(courseId);
  await page.getByLabel("Início *", { exact: true }).fill("2030-12-01T08:00");
  await page.getByLabel("Encerramento *", { exact: true }).fill("2030-12-01T09:00");
  await page.getByLabel("Fuso horário").first().selectOption("America/Recife");
  await page.getByRole("button", { name: "Salvar rascunho" }).click();

  await expect(page.getByRole("status").filter({ hasText: "Rascunho salvo." })).toBeVisible();
  expect(submitted).toMatchObject({
    type_request_id: serviceId,
    campus_id: campusId,
    course_id: courseId,
    starts_at: "2030-12-01T11:00:00.000Z",
    ends_at: "2030-12-01T12:00:00.000Z",
    timezone: "America/Recife",
    status: "draft",
  });
  await expect(page.getByText("Serviço acadêmico de teste").last()).toBeVisible();
  await expect(page.getByText(/America\/Recife/).last()).toBeVisible();
  await page.reload();
  await expect(page.getByRole("heading", { name: "Calendário de atendimento" })).toBeVisible();
  await expect(page.getByRole("button", { name: "Editar janela Serviço acadêmico de teste" })).toBeEnabled();
  await page.getByLabel("Filtrar serviço").selectOption(serviceId);
  await page.getByLabel("Filtrar campus").selectOption(campusId);
  await page.getByLabel("Filtrar curso").selectOption(courseId);
  await expect(page.getByText("1 de 1 janelas")).toBeVisible();

  await page.getByRole("button", { name: "Editar janela" }).click();
  await expect(page.getByRole("heading", { name: "Editar janela" })).toBeFocused();
  await expect(page.getByLabel("Fuso horário").first()).toHaveValue("America/Recife");
  await page.getByLabel("Encerramento *", { exact: true }).fill("2030-12-01T10:00");
  await page.getByRole("button", { name: "Salvar rascunho" }).click();
  await expect(page.getByRole("status").filter({ hasText: "Janela atualizada." })).toBeVisible();
  expect(submitted?.ends_at).toBe("2030-12-01T13:00:00.000Z");
});

test("publicação exige referência e mostra conflito informado pela API", async ({ page }) => {
  await mockCalendar(page);
  let publishAttempts = 0;
  await page.route("**/api/admin/service-opening-windows", async (route) => {
    if (route.request().method() !== "POST") return route.fallback();
    publishAttempts += 1;
    await route.fulfill({ status: 422, json: { message: "Há uma janela publicada sobreposta.", errors: { starts_at: ["Esta janela se sobrepõe a outra janela publicada para o mesmo serviço, campus e curso."] } } });
  });

  await page.goto("/dashboard/admin/calendar");
  await page.getByLabel("Serviço *").selectOption(serviceId);
  await page.getByLabel("Início *", { exact: true }).fill("2030-12-01T08:00");
  await page.getByLabel("Encerramento *", { exact: true }).fill("2030-12-01T09:00");
  await page.getByLabel("Status").first().selectOption("published");
  await expect(page.getByLabel("Referência institucional *")).toBeVisible();
  await page.getByRole("button", { name: "Publicar janela" }).click();
  await expect(page.getByText("Informe a referência institucional antes de publicar.")).toBeVisible();
  expect(publishAttempts).toBe(0);

  await page.getByLabel("Referência institucional *").fill("Referência fictícia de teste automatizado");
  await page.getByRole("button", { name: "Publicar janela" }).click();
  await expect(page.getByRole("alert").filter({ hasText: "Esta janela se sobrepõe" })).toBeVisible();
  expect(publishAttempts).toBe(1);
});

test("registros já existentes não podem ser editados sem confirmação do fuso histórico", async ({ page }) => {
  await mockCalendar(page, () => [draftWindow({ timestamps_utc_normalized: false })]);
  await page.goto("/dashboard/admin/calendar");
  const editButton = page.getByRole("button", { name: "Editar janela Serviço acadêmico de teste" });
  await expect(editButton).toBeDisabled();
  await expect(page.getByText("Confirme o fuso histórico antes de alterar suas datas.")).toBeVisible();
  const accessibility = await new AxeBuilder({ page }).withTags(["wcag2a", "wcag2aa", "wcag21a", "wcag21aa"]).analyze();
  expect(accessibility.violations).toEqual([]);
});

test("erro ao carregar pode ser repetido e a tela de lista vazia é acessível", async ({ page }) => {
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "calendar-test-token"));
  await page.route("**/api/me", (route) => route.fulfill({ json: { id: "admin-calendar-qa", name: "Admin de teste", role: "admin" } }));
  await page.route("**/api/admin/type-requests", (route) => route.fulfill({ json: [{ id: serviceId, name: "Serviço acadêmico de teste" }] }));
  await page.route("**/api/staff-access-scopes/options", (route) => route.fulfill({ json: options }));
  let fail = true;
  await page.route("**/api/admin/service-opening-windows**", (route) => {
    if (fail) return route.fulfill({ status: 503, json: { message: "Serviço temporariamente indisponível." } });
    return route.fulfill({ json: [] });
  });

  await page.goto("/dashboard/admin/calendar");
  await expect(page.getByRole("alert").filter({ hasText: "Serviço temporariamente indisponível." })).toBeVisible();
  fail = false;
  await page.getByRole("button", { name: "Tentar novamente" }).click();
  await expect(page.getByText("Nenhuma janela cadastrada")).toBeVisible();
  const accessibility = await new AxeBuilder({ page }).withTags(["wcag2a", "wcag2aa", "wcag21a", "wcag21aa"]).analyze();
  expect(accessibility.violations).toEqual([]);
});

test("estado de carregamento informa o progresso sem bloquear o conteúdo", async ({ page }) => {
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "calendar-test-token"));
  await page.route("**/api/me", (route) => route.fulfill({ json: { id: "admin-calendar-qa", name: "Admin de teste", role: "admin" } }));
  await page.route("**/api/admin/type-requests", (route) => route.fulfill({ json: [{ id: serviceId, name: "Serviço acadêmico de teste" }] }));
  await page.route("**/api/staff-access-scopes/options", (route) => route.fulfill({ json: options }));
  let release!: () => void;
  const gate = new Promise<void>((resolve) => { release = resolve; });
  await page.route("**/api/admin/service-opening-windows**", async (route) => {
    await gate;
    await route.fulfill({ json: [] });
  });

  await page.goto("/dashboard/admin/calendar");
  await expect(page.getByRole("status").filter({ hasText: "Carregando calendário de atendimento" })).toBeVisible();
  release();
  await expect(page.getByText("Nenhuma janela cadastrada")).toBeVisible();
});

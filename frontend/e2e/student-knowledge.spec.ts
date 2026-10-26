import { expect, test } from "@playwright/test";
import AxeBuilder from "@axe-core/playwright";

const articles = [
  {
    id: "student-article-1", title: "Como solicitar declaração de matrícula", summary: "Veja o que é necessário para solicitar uma declaração.",
    content: "Entre em Meus Pedidos e escolha o serviço. <script>alert('texto ilustrativo')</script>", category: "Matrícula", source_reference: "Referência fictícia de QA", published_at: "2026-10-26T12:00:00.000Z",
  },
  {
    id: "student-article-2", title: "Envio de atestados", summary: "Confira as orientações para anexar seu documento.",
    content: "Envie uma imagem legível do documento pelo formulário do requerimento.", category: "Documentos", source_reference: null, published_at: null,
  },
];

async function mockStudentShell(page: import("@playwright/test").Page) {
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "student-knowledge-token"));
  await page.route("**/api/me", (route) => route.fulfill({ contentType: "application/json", body: JSON.stringify({ id: "student-qa", name: "Aluna QA", email: "aluna@example.test", role: "student" }) }));
  await page.route("**/api/notifications**", (route) => route.fulfill({ contentType: "application/json", body: JSON.stringify({ data: [], unread: 0, current_page: 1, last_page: 1, total: 0 }) }));
}

test("aluno abre a base pelo Help, busca e filtra orientações publicadas com conteúdo em texto", async ({ page }) => {
  const errors: string[] = [];
  const failures: string[] = [];
  const articleFeedRequests: string[] = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });
  page.on("requestfailed", (request) => {
    if (request.failure()?.errorText !== "net::ERR_ABORTED") failures.push(`${request.method()} ${request.url()}`);
  });
  await mockStudentShell(page);
  await page.route("**/api/knowledge-articles/authenticated", async (route) => {
    articleFeedRequests.push(route.request().headers().authorization ?? "");
    await route.fulfill({ contentType: "application/json", body: JSON.stringify(articles) });
  });
  await page.route("**/api/knowledge-articles", () => { throw new Error("A área do aluno não deve consultar o feed público."); });

  await page.goto("/me");
  await page.getByRole("button", { name: "Abrir orientações de uso" }).click();
  await expect(page.getByRole("link", { name: "Ver orientações para estudantes" })).toBeVisible();
  await page.getByRole("link", { name: "Ver orientações para estudantes" }).click();
  await expect(page).toHaveURL(/\/knowledge$/);
  await expect(page.getByRole("heading", { name: "Orientações para estudantes" })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Como solicitar declaração de matrícula" })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Envio de atestados" })).toBeVisible();
  expect(articleFeedRequests).toContain("Bearer student-knowledge-token");
  expect(articleFeedRequests.length).toBeGreaterThan(0);

  await page.getByLabel("Filtrar orientações por categoria").selectOption("Matrícula");
  await expect(page.getByRole("heading", { name: "Como solicitar declaração de matrícula" })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Envio de atestados" })).toHaveCount(0);
  await page.getByLabel("Filtrar orientações por categoria").selectOption("");
  await page.getByRole("searchbox", { name: "Buscar nas orientações" }).fill("atestado");
  await expect(page.getByRole("heading", { name: "Envio de atestados" })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Como solicitar declaração de matrícula" })).toHaveCount(0);
  await page.getByRole("searchbox", { name: "Buscar nas orientações" }).fill("");

  await page.getByText("Consultar orientação completa").first().click();
  await expect(page.getByText("Entre em Meus Pedidos e escolha o serviço.", { exact: false })).toBeVisible();
  await expect(page.locator("article script")).toHaveCount(0);
  await expect(page.getByText("<script>alert('texto ilustrativo')</script>")).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
  const accessibility = await new AxeBuilder({ page }).withTags(["wcag2a", "wcag2aa", "wcag21aa"]).analyze();
  expect(accessibility.violations, JSON.stringify(accessibility.violations, null, 2)).toEqual([]);
  expect(errors, errors.join("\n")).toEqual([]);
  expect(failures, failures.join("\n")).toEqual([]);
  await page.evaluate(() => { if (document.activeElement instanceof HTMLElement) document.activeElement.blur(); });
  await page.screenshot({ path: test.info().outputPath("student-knowledge.png"), fullPage: true });
});

test("feed de orientações mostra erro recuperável e estado vazio", async ({ page }) => {
  let shouldFail = true;
  await mockStudentShell(page);
  await page.route("**/api/knowledge-articles/authenticated", (route) => {
    if (shouldFail) {
      shouldFail = false;
      return route.fulfill({ status: 503, contentType: "application/json", body: JSON.stringify({ message: "unavailable" }) });
    }
    return route.fulfill({ contentType: "application/json", body: "[]" });
  });
  await page.goto("/knowledge");
  await expect(page.locator("div[role=alert].rounded-2xl")).toContainText("Não foi possível carregar as orientações");
  await page.getByRole("button", { name: "Tentar novamente" }).click();
  await expect(page.getByRole("heading", { name: "Ainda não há orientações para estudantes" })).toBeVisible();
});

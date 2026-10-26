import AxeBuilder from "@axe-core/playwright";
import { expect, test } from "@playwright/test";

type MockNotice = Record<string, unknown>;

test("página pública mantém o estado pendente sem revelar rascunhos e oferece o fluxo de solicitação", async ({ page }) => {
  const errors: string[] = [];
  const failures: string[] = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });
  page.on("requestfailed", (request) => failures.push(request.method() + " " + request.url()));
  await page.route("**/api/privacy-notice/current", (route) => route.fulfill({
    contentType: "application/json",
    body: JSON.stringify({ status: "pending_approval", notice: null }),
  }));

  await page.goto("/privacidade");
  await expect(page.getByRole("heading", { name: "Aviso de privacidade" })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Informação institucional pendente de aprovação" })).toBeVisible();
  await expect(page.getByText(/responsável institucional pelo tratamento/)).toBeVisible();
  await expect(page.getByRole("link", { name: "Abrir uma solicitação sobre meus dados" })).toHaveAttribute("href", "/requests/privacidade");
  await expect(page.getByText("Texto interno de rascunho que nunca pode aparecer no público.")).toHaveCount(0);

  const accessibility = await new AxeBuilder({ page }).withTags(["wcag2a", "wcag2aa", "wcag21a", "wcag21aa"]).analyze();
  expect(accessibility.violations, JSON.stringify(accessibility.violations, null, 2)).toEqual([]);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
  await page.evaluate(() => { if (document.activeElement instanceof HTMLElement) document.activeElement.blur(); });
  await page.screenshot({ path: test.info().outputPath("privacy-notice-public.png"), fullPage: true });
  expect(errors, errors.join("\n")).toEqual([]);
  expect(failures, failures.join("\n")).toEqual([]);
});

test("página pública apresenta os dados da versão publicada", async ({ page }) => {
  await page.route("**/api/privacy-notice/current", (route) => route.fulfill({
    contentType: "application/json",
    body: JSON.stringify({
      status: "published",
      notice: {
        version: 7,
        title: "Aviso de privacidade validado em teste",
        content: "Conteúdo público fictício usado apenas para verificar a apresentação da versão aprovada.",
        controller_name: "Instituição fictícia para teste",
        contact_channel: "Canal fictício exclusivo para o teste",
        legal_basis_summary: "Resumo fictício das bases e finalidades em ambiente de teste.",
        retention_summary: "Resumo fictício de retenção que não deve ser usado em produção.",
        source_reference: "Fonte fictícia QA nº 78/2026",
        institutional_approval_reference: "Aprovação fictícia QA nº 79/2026",
        effective_date: "2026-11-01",
        reviewed_at: "2026-10-26T12:00:00.000Z",
        published_at: "2026-10-26T12:00:00.000Z",
      },
    }),
  }));

  await page.goto("/privacidade");
  await expect(page.getByRole("heading", { name: "Aviso de privacidade validado em teste" })).toBeVisible();
  await expect(page.getByText("Versão 7", { exact: true })).toBeVisible();
  await expect(page.getByText("Instituição fictícia para teste", { exact: true })).toBeVisible();
  await expect(page.getByText("Referência da publicação: Fonte fictícia QA nº 78/2026", { exact: true })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Informação institucional pendente de aprovação" })).toHaveCount(0);

  const accessibility = await new AxeBuilder({ page }).withTags(["wcag2a", "wcag2aa", "wcag21a", "wcag21aa"]).analyze();
  expect(accessibility.violations, JSON.stringify(accessibility.violations, null, 2)).toEqual([]);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
  await page.evaluate(() => { if (document.activeElement instanceof HTMLElement) document.activeElement.blur(); });
  await page.screenshot({ path: test.info().outputPath("privacy-notice-published.png"), fullPage: true });
});

test("admin cria, revisa e publica versões com referência institucional e mantém publicada imutável", async ({ page }) => {
  const errors: string[] = [];
  const failures: string[] = [];
  const records: MockNotice[] = [];
  const at = "2026-10-26T12:00:00.000Z";
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });
  page.on("requestfailed", (request) => failures.push(request.method() + " " + request.url()));
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "privacy-notice-admin-token"));
  await page.route("**/api/me", (route) => route.fulfill({
    contentType: "application/json",
    body: JSON.stringify({ id: "privacy-notice-admin", name: "Admin QA", role: "admin" }),
  }));
  await page.route("**/api/notifications**", (route) => route.fulfill({
    contentType: "application/json",
    body: JSON.stringify({ data: [], unread: 0, current_page: 1, last_page: 1, total: 0 }),
  }));
  await page.route("**/api/admin/privacy-notices**", async (route) => {
    const request = route.request();
    const url = new URL(request.url());
    const send = (body: unknown, status = 200) => route.fulfill({ status, contentType: "application/json", body: JSON.stringify(body) });
    const recordId = url.pathname.split("/").filter(Boolean).at(-2);
    const current = records.find((item) => item.id === recordId);

    if (request.method() === "POST" && url.pathname.endsWith("/new-version")) {
      if (!current) return send({ message: "not found" }, 404);
      const created = {
        ...current,
        id: "privacy-notice-2",
        version: 2,
        status: "draft",
        author: { id: "privacy-notice-admin", name: "Admin QA" },
        author_id: "privacy-notice-admin",
        reviewer: null,
        reviewer_id: null,
        source_reference: null,
        institutional_approval_reference: null,
        effective_date: null,
        review_submitted_at: null,
        reviewed_at: null,
        published_at: null,
      };
      records.unshift(created);
      return send(created, 201);
    }

    if (request.method() === "POST" && url.pathname.endsWith("/submit-review")) {
      if (!current) return send({ message: "not found" }, 404);
      Object.assign(current, { status: "review", review_submitted_at: at });
      return send(current);
    }

    if (request.method() === "POST" && url.pathname.endsWith("/return-to-draft")) {
      if (!current) return send({ message: "not found" }, 404);
      Object.assign(current, { status: "draft", review_submitted_at: null });
      return send(current);
    }

    if (request.method() === "POST" && url.pathname.endsWith("/publish")) {
      if (!current) return send({ message: "not found" }, 404);
      Object.assign(current, request.postDataJSON(), {
        status: "published",
        reviewer: { id: "privacy-notice-admin", name: "Admin QA" },
        reviewer_id: "privacy-notice-admin",
        reviewed_at: at,
        published_at: at,
      });
      return send(current);
    }

    if (request.method() === "POST" || request.method() === "PUT") {
      const input = request.postDataJSON() as Record<string, unknown>;
      const existing = request.method() === "PUT" ? records.find((item) => item.id === url.pathname.split("/").at(-1)) : undefined;
      const saved = {
        ...(existing ?? {}), ...input,
        id: existing?.id ?? "privacy-notice-1",
        version: existing?.version ?? 1,
        status: "draft",
        author: { id: "privacy-notice-admin", name: "Admin QA" },
        author_id: "privacy-notice-admin",
        reviewer: null,
        reviewer_id: null,
        review_submitted_at: null,
        reviewed_at: null,
        published_at: null,
        created_at: at,
        updated_at: at,
      };
      if (existing) Object.assign(existing, saved);
      else records.unshift(saved);
      return send(saved, existing ? 200 : 201);
    }

    return send({ data: records, current_page: 1, last_page: 1, total: records.length });
  });

  await page.goto("/dashboard/admin/privacy-notices");
  await expect(page.getByRole("heading", { name: "Avisos de privacidade" })).toBeVisible();
  await expect(page.getByText("Nenhuma versão foi criada.")).toBeVisible();
  await page.getByRole("button", { name: "Novo rascunho" }).click();
  await page.getByLabel("Título", { exact: true }).fill("Aviso institucional de teste");
  await page.getByLabel("Texto do aviso").fill("Conteúdo de teste pendente de validação institucional, não destinado à publicação real.");
  await page.getByRole("button", { name: "Criar rascunho" }).click();
  await expect(page.getByText("Rascunho salvo. Ele não está disponível ao público.")).toBeVisible();
  await expect(page.getByText("v1 · Aviso institucional de teste")).toBeVisible();

  await page.getByRole("button", { name: "Enviar para revisão" }).click();
  await expect(page.getByText("Versão enviada para revisão.")).toBeVisible();
  await page.getByLabel("Responsável institucional pelo tratamento").fill("Instituição fictícia para teste");
  await page.getByLabel("Canal oficial de contato").fill("Canal fictício de teste");
  await page.getByLabel("Bases legais e finalidades").fill("Resumo fictício para teste, sujeito à validação pela instituição.");
  await page.getByLabel("Retenção e descarte").fill("Descrição fictícia de retenção, sujeita à aprovação institucional.");
  await page.getByLabel("Fonte institucional").fill("Ato institucional fictício QA nº 123/2026");
  await page.getByLabel("Referência de aprovação institucional").fill("Referência fictícia QA nº 456/2026");
  await page.getByLabel("Data de vigência").fill("2026-11-01");
  await page.getByRole("button", { name: "Publicar versão aprovada" }).click();
  await expect(page.getByText("Versão publicada com as referências institucionais registradas.")).toBeVisible();
  await expect(page.getByText("Esta versão é imutável.")).toBeVisible();
  await expect(page.getByLabel("Texto do aviso")).toBeDisabled();

  await page.getByRole("button", { name: "Criar nova versão" }).click();
  await expect(page.getByRole("heading", { name: "Versão 2" })).toBeVisible();
  await expect(page.getByLabel("Fonte institucional")).toHaveValue("");
  await expect(page.getByLabel("Referência de aprovação institucional")).toHaveValue("");
  await expect(page.getByText("Nova versão criada como rascunho.")).toBeVisible();

  const accessibility = await new AxeBuilder({ page }).withTags(["wcag2a", "wcag2aa", "wcag21a", "wcag21aa"]).analyze();
  expect(accessibility.violations, JSON.stringify(accessibility.violations, null, 2)).toEqual([]);
  expect(errors, errors.join("\n")).toEqual([]);
  expect(failures, failures.join("\n")).toEqual([]);
  await page.evaluate(() => { if (document.activeElement instanceof HTMLElement) document.activeElement.blur(); });
  await page.screenshot({ path: test.info().outputPath("privacy-notice-admin.png"), fullPage: true });
});

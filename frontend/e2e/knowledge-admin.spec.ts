import { expect, test } from "@playwright/test";
import AxeBuilder from "@axe-core/playwright";

test("admin cria rascunho, envia para revisão e publica com confirmação e fonte", async ({ page }) => {
  const errors: string[] = [];
  const failures: string[] = [];
  const articles: Array<Record<string, unknown>> = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });
  page.on("requestfailed", (request) => failures.push(`${request.method()} ${request.url()}`));
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "qa-admin-token"));
  await page.route("**/api/me", (route) => route.fulfill({
    contentType: "application/json",
    body: JSON.stringify({ id: "admin-qa", name: "Admin QA", role: "admin" }),
  }));
  await page.route("**/api/notifications**", (route) => route.fulfill({
    contentType: "application/json",
    body: JSON.stringify({ data: [], unread: 0, current_page: 1, last_page: 1, total: 0 }),
  }));
  await page.route("**/api/admin/knowledge-articles**", async (route) => {
    const request = route.request();
    const url = new URL(request.url());
    const json = (body: unknown, status = 200) => route.fulfill({ status, contentType: "application/json", body: JSON.stringify(body) });
    if (request.method() === "POST" || request.method() === "PUT") {
      const input = request.postDataJSON() as Record<string, unknown>;
      const current = request.method() === "PUT"
        ? articles.find((item) => item.id === url.pathname.split("/").at(-1))
        : undefined;
      const previousVersions = (current?.versions as Array<Record<string, unknown>> | undefined) ?? [];
      const nextVersion = {
        id: `article-qa-1-version-${previousVersions.length + 1}`,
        version: previousVersions.length + 1,
        event: input.status === "review" ? "submitted_for_review" : input.status === "published" ? "published" : "draft_saved",
        status: input.status,
        title: input.title,
        summary: input.summary,
        content: input.content,
        category: input.category,
        audience: input.audience,
        source_reference: input.source_reference,
        created_by_name: "Admin QA",
        reviewed_by_name: input.status === "published" ? "Admin QA" : null,
        acted_by_name: "Admin QA",
        publication_confirmed: input.publication_confirmed === true,
        publication_confirmed_at: input.publication_confirmed === true ? "2026-10-26T12:00:00.000Z" : null,
        published_at: input.status === "published" ? "2026-10-26T12:00:00.000Z" : null,
        created_at: "2026-10-26T12:00:00.000Z",
      };
      const publishedVersion: Record<string, unknown> | null = input.status === "published"
        ? nextVersion
        : (current?.published_version as Record<string, unknown> | null | undefined) ?? null;
      const saved = {
        ...(current ?? {}), ...input,
        id: current?.id ?? "article-qa-1",
        updated_by: "admin-qa",
        can_publish: true,
        created_at: "2026-10-26T12:00:00.000Z",
        updated_at: "2026-10-26T12:00:00.000Z",
        published_at: input.status === "published" ? "2026-10-26T12:00:00.000Z" : current?.published_at ?? null,
        published_version_id: publishedVersion?.id ?? current?.published_version_id ?? null,
        published_version: publishedVersion,
        publication_active: input.status === "published" ? true : input.status === "inactive" ? false : current?.publication_active ?? false,
        versions: [...previousVersions, nextVersion],
      };
      if (current) Object.assign(current, saved);
      else articles.unshift(saved);
      return json(saved, request.method() === "POST" ? 201 : 200);
    }
    const filtered = articles.filter((item) => !url.searchParams.get("status") || item.status === url.searchParams.get("status"));
    return json({ data: filtered, current_page: 1, last_page: 1, total: filtered.length });
  });

  await page.goto("/dashboard/admin/knowledge");
  await expect(page.getByRole("heading", { name: "Base de conhecimento" })).toBeVisible();
  await page.getByRole("button", { name: "Nova orientação" }).click();
  await page.getByLabel("Título", { exact: true }).fill("Orientação fictícia de QA");
  await page.getByLabel("Resumo", { exact: true }).fill("Resumo demonstrativo para validar a revisão.");
  await page.getByLabel("Orientação em texto simples").fill("Conteúdo fictício. Não é uma regra institucional.");
  await page.getByRole("button", { name: "Salvar rascunho" }).click();
  await expect(page.getByText("Rascunho salvo.", { exact: true })).toBeVisible();
  await page.getByLabel("Fonte ou referência").fill("Referência fictícia exclusiva para teste");
  await page.getByRole("button", { name: "Enviar para revisão" }).click();
  await expect(page.getByText("Orientação enviada para revisão.", { exact: true })).toBeVisible();
  await expect(page.getByRole("button", { name: /Orientação fictícia de QA/ })).toBeVisible();

  await expect(page.getByRole("heading", { name: /Versão pendente · v2 · Em revisão/ })).toBeVisible();
  await page.getByRole("button", { name: "Publicar versão revisada" }).click();
  await expect(page.getByText("Confirme que revisou o conteúdo e que a publicação está autorizada.", { exact: true })).toBeVisible();
  expect(articles[0].status).toBe("review");

  await page.getByRole("checkbox", { name: /Confirmo que revisei o conteúdo/ }).check();
  page.once("dialog", (dialog) => dialog.accept());
  await page.getByRole("button", { name: "Publicar versão revisada" }).click();
  await expect(page.getByText("Orientação publicada.", { exact: true })).toBeVisible();
  expect(articles[0]).toMatchObject({ status: "published", source_reference: "Referência fictícia exclusiva para teste" });
  await expect(page.getByRole("heading", { name: "Última versão publicada · v3" })).toBeVisible();
  await expect(page.getByText("Histórico de versões (3)")).toBeVisible();

  await page.getByRole("textbox", { name: "Título", exact: true }).fill("Revisão pendente sem substituir publicação");
  await page.getByRole("textbox", { name: "Resumo", exact: true }).fill("Resumo da próxima versão ainda em revisão.");
  await page.getByRole("textbox", { name: /Orientação em texto simples/ }).fill("Texto novo, aguardando aprovação editorial.");
  await page.getByRole("button", { name: "Salvar rascunho" }).click();
  await expect(page.getByRole("heading", { name: "Última versão publicada · v3" })).toBeVisible();
  await expect(page.getByRole("heading", { name: /Versão pendente · v4 · Rascunho/ })).toBeVisible();
  await page.getByRole("button", { name: "Enviar para revisão" }).click();
  await expect(page.getByText("Orientação enviada para revisão.", { exact: true })).toBeVisible();
  await expect(page.getByRole("heading", { name: "Última versão publicada · v3" })).toBeVisible();
  await expect(page.getByRole("heading", { name: /Versão pendente · v5 · Em revisão/ })).toBeVisible();
  await expect(page.getByText("Histórico de versões (5)")).toBeVisible();
  await page.getByText("Histórico de versões (5)").click();
  await page.getByText("Ver conteúdo desta versão").first().click();
  const latestRevision = page.getByRole("listitem").filter({ hasText: "v5 · Em revisão" });
  await expect(latestRevision.locator("pre")).toHaveText("Texto novo, aguardando aprovação editorial.");
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
  const accessibility = await new AxeBuilder({ page }).withTags(["wcag2a", "wcag2aa", "wcag21aa"]).analyze();
  expect(accessibility.violations, JSON.stringify(accessibility.violations, null, 2)).toEqual([]);
  expect(errors, errors.join("\n")).toEqual([]);
  expect(failures, failures.join("\n")).toEqual([]);
});

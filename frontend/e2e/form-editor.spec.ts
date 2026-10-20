import { expect, test } from "@playwright/test";

test("administra versão de formulário sem publicar alterações antes da confirmação", async ({ page }) => {
  const errors: string[] = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });
  page.on("dialog", (dialog) => dialog.accept());
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "qa-admin-token"));

  const typeId = "11111111-1111-4111-8111-111111111111";
  let versions: Array<{ id: string; version: number; status: string; schema: { fields: unknown[] }; change_summary: string | null; created_at: string; published_at: string | null }> = [];
  let publishCalls = 0;
  await page.route("**/api/**", async (route) => {
    const url = new URL(route.request().url());
    const body = route.request().postDataJSON?.() as { schema?: { fields: unknown[] }; change_summary?: string } | undefined;
    const json = (value: unknown, status = 200) => route.fulfill({ status, contentType: "application/json", body: JSON.stringify(value) });
    if (url.pathname === "/api/me") return json({ user: { id: 1, name: "Admin QA", role: "admin", must_change_password: false } });
    if (url.pathname === "/api/notifications") return json({ data: [] });
    if (url.pathname === "/api/notifications/count") return json({ unread: 0 });
    if (url.pathname === "/api/type-requests") return json([{ id: typeId, name: "Ementa QA" }]);
    if (url.pathname === `/api/type-requests/${typeId}/form-versions` && route.request().method() === "GET") return json(versions);
    if (url.pathname === `/api/type-requests/${typeId}/form-versions` && route.request().method() === "POST") {
      const version = { id: "22222222-2222-4222-8222-222222222222", version: 1, status: "draft", schema: body?.schema ?? { fields: [] }, change_summary: body?.change_summary ?? null, created_at: "2026-10-12T12:00:00Z", published_at: null };
      versions = [version];
      return json(version, 201);
    }
    if (url.pathname === `/api/form-versions/${versions[0]?.id}/publish`) {
      publishCalls++;
      versions = [{ ...versions[0], status: "published", published_at: "2026-10-12T12:01:00Z" }];
      return json(versions[0]);
    }
    return json({ message: "Rota QA não configurada" }, 404);
  });

  await page.goto(`/dashboard/admin/types/${typeId}/form`);
  await expect(page.getByRole("heading", { name: "Formulário de Ementa QA" })).toBeVisible();
  await page.getByRole("button", { name: "Adicionar campo" }).click();
  await page.getByRole("textbox", { name: "Chave técnica" }).fill("motivo_qa");
  await page.getByRole("textbox", { name: "Rótulo visível" }).fill("Motivo QA");
  await page.getByRole("textbox", { name: "Resumo da mudança" }).fill("Inclui motivo para validação fictícia");
  await expect(page.getByRole("textbox", { name: "Motivo QA" })).toBeVisible();
  await page.getByRole("button", { name: "Salvar rascunho" }).click();
  await expect(page.getByRole("status")).toContainText("Rascunho da versão 1 salvo");
  expect(versions[0].status).toBe("draft");
  expect(publishCalls).toBe(0);
  await page.getByRole("button", { name: "Publicar versão" }).click();
  await expect(page.getByRole("status")).toContainText("Versão 1 publicada");
  expect(publishCalls).toBe(1);
  await expect(page.getByText("Versão somente para consulta")).toBeVisible();
  const width = await page.evaluate(() => ({ document: document.documentElement.scrollWidth, viewport: window.innerWidth }));
  expect(width.document).toBeLessThanOrEqual(width.viewport);
  expect(errors, errors.join("\n")).toEqual([]);
});

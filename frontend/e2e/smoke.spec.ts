import { test, expect } from "@playwright/test";

test.describe("smoke público do Chat Request", () => {
  test("página inicial renderiza sem erro de console ou rede", async ({ page }) => {
    const consoleErrors: string[] = [];
    const failedRequests: string[] = [];
    page.on("console", (message) => { if (message.type() === "error") consoleErrors.push(message.text()); });
    page.on("requestfailed", (request) => failedRequests.push(`${request.method()} ${request.url()}`));
    await page.goto("/");
    await expect(page).toHaveTitle(/Chat Request|Login|Requerimento/i);
    await expect(page.locator("body")).toContainText(/Chat Request|Entrar|Login|requerimento/i);
    expect(consoleErrors, consoleErrors.join("\n")).toEqual([]);
    expect(failedRequests, failedRequests.join("\n")).toEqual([]);
  });

  test("login exibe validação para credenciais inválidas", async ({ page }) => {
    await page.goto("/login");
    const email = page.locator('input').nth(0);
    const password = page.locator('input[type="password"]').first();
    await expect(email).toBeVisible();
    await email.fill("qa.invalid@example.test");
    await password.fill("senha-invalida");
    await page.locator('button[type="submit"]').click();
    await expect(page.locator("body")).toContainText(/inválid|incorret|erro|credencial/i);
  });

  test("catálogo público mostra serviços e orientações publicados", async ({ page }) => {
    await page.route("**/api/service-catalog", async (route) => route.fulfill({
      contentType: "application/json",
      body: JSON.stringify([{
        id: "service-1", name: "Declaração de matrícula", description: "Solicite sua declaração.", category: "Documentos", audience: "Estudantes", channel: "digital", channel_instructions: null, responsible_sector: "CRADT", documentation: { required: false, instructions: null },
      }]),
    }));
    await page.route("**/api/knowledge-articles", async (route) => route.fulfill({
      contentType: "application/json",
      body: JSON.stringify([{
        id: "article-1", title: "Como acompanhar", summary: "Consulte o andamento do pedido.", content: "Acompanhe pelo menu Meus pedidos.", category: "Acompanhamento", source_reference: null, published_at: "2026-09-12T12:00:00Z",
      }]),
    }));

    await page.goto("/servicos");
    await expect(page.getByRole("heading", { name: "Catálogo de serviços" })).toBeVisible();
    await expect(page.getByText("Declaração de matrícula")).toBeVisible();
    await expect(page.getByText("Como acompanhar")).toBeVisible();
    await page.getByRole("textbox", { name: "Buscar serviços e orientações" }).fill("declaração");
    await expect(page.getByText("Declaração de matrícula")).toBeVisible();
    await expect(page.getByText("Como acompanhar")).not.toBeVisible();
  });
});

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
});

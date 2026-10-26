import { expect, test } from "@playwright/test";
import AxeBuilder from "@axe-core/playwright";

test("aluno encontra convite de satisfação e filtra notificações por categoria", async ({ page }) => {
  const errors: string[] = [];
  const failedRequests: string[] = [];
  const notificationRequests: string[] = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });
  page.on("requestfailed", (request) => failedRequests.push(request.url()));

  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "student-token"));
  await page.route("**/api/me", (route) => route.fulfill({ contentType: "application/json", body: JSON.stringify({ id: "student-1", name: "Estudante QA", email: "qa@example.test", role: "student" }) }));
  await page.route("**/api/notifications**", async (route) => {
    const url = new URL(route.request().url());
    if (url.pathname.endsWith("/notifications/count")) {
      await route.fulfill({ contentType: "application/json", body: JSON.stringify({ unread: 1 }) });
      return;
    }
    notificationRequests.push(url.searchParams.get("category") ?? "all");
    const notification = {
      id: "notification-satisfaction",
      title: "Avalie o atendimento",
      body: "Sua avaliação é opcional e está disponível nos detalhes do requerimento.",
      category: "satisfaction",
      link: "/requests/visualizar/request-1",
      read_at: null,
      created_at: "2026-10-26T12:00:00.000Z",
    };
    await route.fulfill({ contentType: "application/json", body: JSON.stringify({ data: [notification], unread: 1, current_page: 1, last_page: 1, total: 1, per_page: 20 }) });
  });

  await page.goto("/notifications");
  await expect(page.getByRole("heading", { name: "Notificações" })).toBeVisible();
  const markAllButton = page.getByRole("button", { name: "Marcar todas como lidas" });
  await expect(markAllButton).toBeVisible();
  const buttonBounds = await markAllButton.boundingBox();
  const titleBounds = await page.getByRole("heading", { name: "Notificações" }).boundingBox();
  expect(buttonBounds).not.toBeNull();
  expect(titleBounds).not.toBeNull();
  expect(buttonBounds!.x + buttonBounds!.width).toBeLessThanOrEqual(page.viewportSize()!.width);
  expect(buttonBounds!.height).toBeGreaterThanOrEqual(44);
  const separated = buttonBounds!.x >= titleBounds!.x + titleBounds!.width
    || titleBounds!.x >= buttonBounds!.x + buttonBounds!.width
    || buttonBounds!.y >= titleBounds!.y + titleBounds!.height
    || titleBounds!.y >= buttonBounds!.y + buttonBounds!.height;
  expect(separated).toBeTruthy();
  await expect(page.getByText("Avalie o atendimento")).toBeVisible();
  await expect(page.locator("span").filter({ hasText: /^Pesquisa de satisfação$/ })).toBeVisible();
  await expect(page.getByRole("link", { name: "Abrir requerimento" })).toHaveAttribute("href", "/requests/visualizar/request-1");
  await page.getByRole("combobox", { name: "Categoria da notificação" }).selectOption("satisfaction");
  await expect.poll(() => notificationRequests.includes("satisfaction")).toBeTruthy();

  const accessibility = await new AxeBuilder({ page }).withTags(["wcag2a", "wcag2aa", "wcag21aa"]).analyze();
  expect(accessibility.violations, JSON.stringify(accessibility.violations, null, 2)).toEqual([]);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
  expect(errors, errors.join("\n")).toEqual([]);
  expect(failedRequests, failedRequests.join("\n")).toEqual([]);
  await page.screenshot({ path: test.info().outputPath("notifications.png"), fullPage: true });
});

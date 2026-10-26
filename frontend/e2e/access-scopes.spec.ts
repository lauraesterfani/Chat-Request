import { test, expect } from "@playwright/test";

test.use({ timezoneId: "America/Sao_Paulo" });

test("admin cadastra campus, associa curso e preserva início local da concessão", async ({ page }) => {
  const campuses: Array<{ id: string; name: string; code: string; is_active: boolean; courses_count: number }> = [];
  const courses = [{ id: "course-1", name: "Tecnologia em Sistemas", code: "TSI", campus_id: null as string | null }];
  const grants: Array<Record<string, unknown>> = [];
  const submittedGrant: { value?: Record<string, unknown> } = {};

  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "qa-admin-token"));
  await page.route("**/api/me", (route) => route.fulfill({
    contentType: "application/json",
    body: JSON.stringify({ id: 1, name: "Admin QA", email: "admin@example.test", role: "admin", capabilities: ["manage_access"] }),
  }));
  await page.route("**/api/notifications**", (route) => route.fulfill({
    contentType: "application/json",
    body: JSON.stringify({ data: [], unread: 0, current_page: 1, last_page: 1, total: 0 }),
  }));
  await page.route("**/api/staff-access-scopes**", async (route) => {
    const request = route.request();
    const url = new URL(request.url());
    if (request.method() === "DELETE") {
      const grantId = Number(url.pathname.split("/").at(-1));
      const grant = grants.find((item) => item.id === grantId);
      if (grant) grant.revoked_at = new Date().toISOString();
      return route.fulfill({ contentType: "application/json", body: JSON.stringify({ message: "Concessão revogada." }) });
    }
    if (request.method() === "POST") {
      submittedGrant.value = request.postDataJSON() as Record<string, unknown>;
      const grant = { id: 7, ...submittedGrant.value, staff_admin: { name: "Agente QA", role: "cradt" }, campus: campuses[0] ?? null, course: courses[0] ?? null };
      grants.unshift(grant);
      return route.fulfill({ status: 201, contentType: "application/json", body: JSON.stringify(grant) });
    }
    if (url.pathname.endsWith("/options")) {
      return route.fulfill({ contentType: "application/json", body: JSON.stringify({
        staff: [{ id: 2, name: "Agente QA", role: "cradt" }],
        campuses: campuses.filter((campus) => campus.is_active),
        courses,
        sectors: ["CRADT", "COORDENACAO"],
      }) });
    }
    return route.fulfill({ contentType: "application/json", body: JSON.stringify({ data: grants, current_page: 1, last_page: 1, total: grants.length }) });
  });
  await page.route("**/api/admin/campuses**", async (route) => {
    if (route.request().method() === "POST") {
      const data = route.request().postDataJSON() as { name: string; code: string };
      const campus = { id: "campus-1", ...data, is_active: true, courses_count: 0 };
      campuses.push(campus);
      return route.fulfill({ status: 201, contentType: "application/json", body: JSON.stringify(campus) });
    }
    return route.fulfill({ contentType: "application/json", body: JSON.stringify({ data: campuses, current_page: 1, last_page: 1, total: campuses.length }) });
  });
  await page.route("**/api/admin/courses/*/campus", async (route) => {
    const data = route.request().postDataJSON() as { campus_id: string | null };
    courses[0].campus_id = data.campus_id;
    const campus = campuses.find((item) => item.id === data.campus_id);
    if (campus) campus.courses_count = 1;
    return route.fulfill({ contentType: "application/json", body: JSON.stringify({ ...courses[0], campus }) });
  });

  const errors: string[] = [];
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });
  await page.goto("/dashboard/admin/access-scopes");
  await expect(page.getByRole("heading", { name: "Campi e vínculos dos cursos" })).toBeVisible();

  await page.getByLabel("Nome do campus").fill("Campus QA");
  await page.getByLabel("Código").fill("QA-01");
  await page.getByRole("button", { name: "Cadastrar campus" }).click();
  await expect(page.getByText("Campus QA · QA-01 · 0 cursos")).toBeVisible();

  await page.getByRole("combobox", { name: "Curso", exact: true }).selectOption("course-1");
  await page.getByRole("combobox", { name: "Campus", exact: true }).selectOption("campus-1");
  await page.getByRole("button", { name: "Salvar vínculo" }).click();
  await expect(page.getByText("Campus QA · QA-01 · 1 curso")).toBeVisible();

  await page.getByLabel("Pessoa da equipe").selectOption("2");
  await page.getByLabel("Campus (opcional)").selectOption("campus-1");
  await page.getByLabel("Curso (opcional)").selectOption("course-1");
  await page.getByLabel("Setor (opcional)").selectOption("CRADT");
  const localStart = new Date(Date.now() + 90 * 60_000);
  localStart.setMinutes(localStart.getMinutes() - localStart.getTimezoneOffset());
  const localStartValue = localStart.toISOString().slice(0, 16);
  await page.getByLabel("Início do acesso").fill(localStartValue);
  await page.getByLabel("Justificativa").fill("Cobertura temporária de teste.");
  await page.getByRole("button", { name: "Conceder acesso" }).click();
  await expect(page.getByText("Acesso concedido e registrado.")).toBeVisible();
  expect(submittedGrant.value?.starts_at).toBe(new Date(localStartValue).toISOString());
  expect(submittedGrant.value?.starts_at).toBeTruthy();
  await expect(page.getByText("Agendado")).toBeVisible();
  await expect(page.getByRole("button", { name: "Revogar" })).toBeVisible();
  page.once("dialog", (dialog) => dialog.accept());
  await page.getByRole("button", { name: "Revogar" }).click();
  await expect(page.getByText("Revogado")).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
  expect(errors).toEqual([]);
  await page.evaluate(() => {
    window.scrollTo(0, 0);
    if (document.activeElement instanceof HTMLElement) document.activeElement.blur();
  });
  await page.screenshot({ path: `/tmp/chat-request-phase8-${test.info().project.name}.png`, fullPage: true });
});

import { test, expect } from '@playwright/test';

/**
 * REQ-DEMO-013 — happy-path e2e against the ControllerKit demo: the UiKit shell, the FormKit redirect form
 * (RedirectToRefererTrait), SafeForwardTrait and the formula-safe download responses.
 */
test.describe('ControllerKit demo', () => {
  test('home renders the UiKit shell and the traits', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.ok()).toBeTruthy();
    await expect(page.locator('[data-testid="demo-masthead"]')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Test SafeForwardTrait' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'formula-safe CSV' })).toBeVisible();
  });

  test('the redirect form posts and comes back to the referer', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('button', { name: 'Test RedirectToRefererTrait' }).click();
    await expect(page).toHaveURL(/from_demo=1/);
    await expect(page.locator('[data-testid="demo-masthead"]')).toBeVisible();
  });

  test('SafeForwardTrait reaches the forwarded action', async ({ page }) => {
    const response = await page.goto('/forward');
    expect(response?.ok()).toBeTruthy();
    await expect(page.locator('body')).toContainText('SafeForwardTrait target action reached.');
  });

  test('the CSV download neutralises formula cells', async ({ request }) => {
    const response = await request.get('/download.csv');
    expect(response.ok()).toBeTruthy();
    expect(response.headers()['content-disposition']).toContain('attachment');
    const body = await response.text();
    expect(body).toContain("'=1+1");
    expect(body).not.toMatch(/(^|,)=1\+1/m);
  });

  test('the JSON download is an attachment sent with no-store', async ({ request }) => {
    const response = await request.get('/download.json');
    expect(response.ok()).toBeTruthy();
    expect(response.headers()['content-disposition']).toContain('attachment');
    expect(response.headers()['cache-control']).toContain('no-store');
    expect((await response.json()).bundle).toBe('nowo-tech/controller-kit-bundle');
  });
});

import { test, expect } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { resolve } from 'node:path';
import { frameShot } from './frame';

/**
 * REQ-DEMO-013 — full demo frame (demo platform shell + ControllerKit demo page, no WebProfiler toolbar), captured as a
 * padded union clip with a tall viewport (see frame.ts).
 */
const outDir = process.env.SCREENSHOT_DIR
  ? resolve(process.env.SCREENSHOT_DIR)
  : resolve(__dirname, '../../../../docs/images/demo');

test.use({ viewport: { width: 1360, height: 900 } });

test.beforeAll(() => {
  mkdirSync(outDir, { recursive: true });
});

test('overview — traits and redirect form', async ({ page }) => {
  await page.goto('/');
  await expect(page.getByRole('button', { name: 'Test RedirectToRefererTrait' })).toBeVisible();
  await frameShot(page, resolve(outDir, 'overview.png'));
});

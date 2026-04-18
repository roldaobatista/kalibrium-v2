import { test, expect } from '@playwright/test';

/**
 * @covers AC-006 layout adaptativo renderiza sem overflow em mobile e desktop
 *
 * Slice 016 — E15-S02. Under the current red state (no React/Ionic scaffold),
 * /home does not exist and this test fails. It must turn green once the
 * implementer creates src/pages/HomePage.tsx with an Ionic Grid that fits
 * both viewports.
 */

const VIEWPORTS = [
    { label: 'mobile-375x667', width: 375, height: 667 },
    { label: 'desktop-1280x800', width: 1280, height: 800 },
];

test.describe('AC-006: /home has no horizontal overflow on mobile and desktop viewports', () => {
    for (const vp of VIEWPORTS) {
        test(`AC-006: viewport ${vp.label} — scrollWidth <= clientWidth @smoke`, async ({ page, baseURL }) => {
            await page.setViewportSize({ width: vp.width, height: vp.height });

            const response = await page.goto(`${baseURL}/home`, { waitUntil: 'networkidle' });
            expect(response, `GET /home must respond (viewport=${vp.label})`).not.toBeNull();
            expect(response!.status(), `/home must return 2xx on ${vp.label}`).toBeLessThan(400);

            // Wait for Ionic mount.
            await page.waitForSelector('ion-app', { timeout: 10_000 });

            const metrics = await page.evaluate(() => {
                const doc = document.documentElement;
                const body = document.body;
                return {
                    docScrollWidth: doc.scrollWidth,
                    docClientWidth: doc.clientWidth,
                    bodyScrollWidth: body.scrollWidth,
                    bodyClientWidth: body.clientWidth,
                    innerWidth: window.innerWidth,
                };
            });

            // AC-006: documentElement.scrollWidth must be <= clientWidth
            expect(
                metrics.docScrollWidth,
                `documentElement.scrollWidth (${metrics.docScrollWidth}) > clientWidth (${metrics.docClientWidth}) on ${vp.label}`,
            ).toBeLessThanOrEqual(metrics.docClientWidth);

            // And body must not overflow either.
            expect(
                metrics.bodyScrollWidth,
                `body.scrollWidth (${metrics.bodyScrollWidth}) > body.clientWidth (${metrics.bodyClientWidth}) on ${vp.label}`,
            ).toBeLessThanOrEqual(metrics.bodyClientWidth);
        });
    }
});

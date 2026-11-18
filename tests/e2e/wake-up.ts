import type { FullConfig } from '@playwright/test';

/** Render's free service sleeps after 15 idle minutes and takes about a minute to wake. */
export default async function wakeUp(config: FullConfig): Promise<void> {
    const base = config.projects[0].use.baseURL as string;
    const deadline = Date.now() + 4 * 60_000;

    while (Date.now() < deadline) {
        try {
            const res = await fetch(`${base}/up`, { signal: AbortSignal.timeout(20_000) });
            if (res.ok) return;
        } catch {
            // still waking
        }
        await new Promise((r) => setTimeout(r, 5_000));
    }
    throw new Error(`${base} did not wake up within 4 minutes`);
}

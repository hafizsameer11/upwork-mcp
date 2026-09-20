"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";

type Today = {
  jobs_found: number;
  strong_matches: number;
  drafts: number;
  awaiting_approval: number;
  submitted: number;
  replies: number;
  unread_messages: number;
  strong_jobs: Array<{ id: number; title: string; overall_match: number }>;
  awaiting: Array<{ id: number; job?: { title: string } }>;
};

export default function HomePage() {
  const router = useRouter();
  const [data, setData] = useState<Today | null>(null);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!localStorage.getItem("token")) {
      router.replace("/login");
      return;
    }
    api<Today>("/dashboard/today")
      .then(setData)
      .catch((e) => setError(e.message));

    let echo: ReturnType<typeof import("@/lib/echo").getEcho> = null;
    import("@/lib/echo").then(({ getEcho }) => {
      echo = getEcho();
      echo?.channel("dashboard").listen(".job.strong_matched", () => {
        api<Today>("/dashboard/today").then(setData).catch(() => undefined);
      });
    });

    return () => {
      echo?.leave("dashboard");
    };
  }, [router]);

  if (error) return <p className="text-red-400">{error}</p>;
  if (!data) return <p className="text-zinc-500">Loading…</p>;

  const cards = [
    ["Jobs Found", data.jobs_found],
    ["Strong Matches", data.strong_matches],
    ["Drafts", data.drafts],
    ["Awaiting Approval", data.awaiting_approval],
    ["Submitted", data.submitted],
    ["Replies", data.replies],
  ];

  return (
    <div className="space-y-8">
      <header>
        <h1 className="text-3xl font-semibold tracking-tight">Today</h1>
        <p className="text-zinc-400 mt-1">Live BD pipeline — AI drafts, humans approve before Connects.</p>
      </header>

      <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        {cards.map(([label, value]) => (
          <div key={label as string} className="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4">
            <div className="text-xs text-zinc-500 uppercase tracking-wide">{label}</div>
            <div className="text-2xl font-semibold mt-2">{value}</div>
          </div>
        ))}
      </div>

      <div className="grid md:grid-cols-2 gap-6">
        <section className="rounded-xl border border-zinc-800 p-5">
          <h2 className="font-medium mb-4">Strong Matches</h2>
          <ul className="space-y-3">
            {data.strong_jobs.map((j) => (
              <li key={j.id} className="flex justify-between gap-4 text-sm">
                <Link href={`/jobs/${j.id}`} className="text-emerald-300 hover:underline">
                  {j.title}
                </Link>
                <span className="text-zinc-400">{j.overall_match}%</span>
              </li>
            ))}
            {!data.strong_jobs.length && <li className="text-zinc-500 text-sm">No strong matches yet.</li>}
          </ul>
        </section>
        <section className="rounded-xl border border-zinc-800 p-5">
          <h2 className="font-medium mb-4">Awaiting Approval</h2>
          <ul className="space-y-3">
            {data.awaiting.map((p) => (
              <li key={p.id} className="flex justify-between gap-4 text-sm">
                <span>{p.job?.title || `Proposal #${p.id}`}</span>
                <Link href={`/proposals/${p.id}`} className="text-emerald-300 hover:underline">
                  Review
                </Link>
              </li>
            ))}
            {!data.awaiting.length && <li className="text-zinc-500 text-sm">Nothing waiting.</li>}
          </ul>
        </section>
      </div>
    </div>
  );
}

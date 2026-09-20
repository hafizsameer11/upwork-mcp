"use client";

import { FormEvent, useEffect, useRef, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { api } from "@/lib/api";

type ChatMsg = { id: number; role: string; content: string; meta?: any };

export default function JobDetailPage() {
  const { id } = useParams<{ id: string }>();
  const router = useRouter();
  const [job, setJob] = useState<any>(null);
  const [busy, setBusy] = useState("");
  const [error, setError] = useState("");
  const [chat, setChat] = useState<ChatMsg[]>([]);
  const [message, setMessage] = useState("");
  const chatEnd = useRef<HTMLDivElement>(null);

  async function load() {
    const data = await api(`/jobs/${id}`);
    setJob(data);
  }

  useEffect(() => {
    load().catch((e) => setError(e.message));
    api<ChatMsg[]>(`/jobs/${id}/chat`).then(setChat).catch(() => undefined);
  }, [id]);

  useEffect(() => {
    chatEnd.current?.scrollIntoView({ behavior: "smooth" });
  }, [chat]);

  async function reanalyze() {
    setBusy("analyze");
    setError("");
    try {
      const data = await api(`/jobs/${id}/reanalyze?sync=1`, { method: "POST" });
      setJob(data);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Analyze failed");
    } finally {
      setBusy("");
    }
  }

  async function generate() {
    setBusy("generate");
    setError("");
    try {
      const proposal = await api<any>(`/jobs/${id}/proposals`, { method: "POST" });
      router.push(`/proposals/${proposal.id}`);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed");
    } finally {
      setBusy("");
    }
  }

  async function sendChat(e?: FormEvent, preset?: string) {
    e?.preventDefault();
    const text = (preset ?? message).trim();
    if (!text) return;
    setBusy("chat");
    setError("");
    setMessage("");
    setChat((prev) => [...prev, { id: Date.now(), role: "user", content: text }]);
    try {
      const res = await api<{ history: ChatMsg[] }>(`/jobs/${id}/chat`, {
        method: "POST",
        body: JSON.stringify({ message: text }),
      });
      setChat(res.history);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Chat failed");
    } finally {
      setBusy("");
    }
  }

  if (!job) return <p className="text-zinc-500">{error || "Loading…"}</p>;

  const a = job.analysis;
  const recColor =
    a?.recommendation === "pursue"
      ? "text-emerald-400"
      : a?.recommendation === "skip"
        ? "text-red-400"
        : "text-amber-400";

  return (
    <div className="grid xl:grid-cols-[1.2fr_0.8fr] gap-6 items-start">
      <div className="space-y-6 min-w-0">
        <header className="space-y-2">
          <div className="flex flex-wrap items-center gap-3 text-sm">
            <span className="text-emerald-400 font-medium">{job.overall_match ?? "—"}% match</span>
            {a?.recommendation && (
              <span className={`uppercase tracking-wide ${recColor}`}>AI: {a.recommendation}</span>
            )}
            {job.profile_gap && <span className="text-amber-400">⚠ PROFILE GAP</span>}
          </div>
          <h1 className="text-3xl font-semibold">{job.title}</h1>
          <p className="text-sm text-zinc-500">
            Budget {job.budget_min ?? "?"}–{job.budget_max ?? "?"} · {job.experience_level || "level n/a"} ·{" "}
            {job.proposal_count ?? "?"} proposals
          </p>
        </header>

        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
          {[
            ["Technical", job.technical_match],
            ["Portfolio", job.portfolio_match],
            ["Profile", job.profile_match],
            ["Client", job.client_quality],
          ].map(([l, v]) => (
            <div key={l as string} className="border border-zinc-800 rounded-xl p-4">
              <div className="text-xs text-zinc-500">{l}</div>
              <div className="text-xl mt-1">{v ?? "—"}%</div>
            </div>
          ))}
        </div>

        {a ? (
          <section className="border border-zinc-800 rounded-xl p-5 space-y-4">
            <div className="flex items-center justify-between gap-3">
              <h2 className="font-medium">AI thoughts & analysis</h2>
              <button
                onClick={reanalyze}
                disabled={!!busy}
                className="text-xs rounded-lg border border-zinc-700 px-3 py-1.5 disabled:opacity-50"
              >
                {busy === "analyze" ? "Analyzing…" : "Re-analyze"}
              </button>
            </div>
            {a.summary && <p className="text-sm text-zinc-200">{a.summary}</p>}
            {a.thoughts && (
              <div>
                <div className="text-xs uppercase tracking-wide text-zinc-500 mb-1">Detailed thoughts</div>
                <p className="text-sm text-zinc-300 whitespace-pre-wrap leading-relaxed">{a.thoughts}</p>
              </div>
            )}
            {a.win_strategy && (
              <div className="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-3 text-sm">
                <div className="text-emerald-300 text-xs uppercase mb-1">Win strategy</div>
                {a.win_strategy}
              </div>
            )}
            <div className="grid md:grid-cols-2 gap-4 text-sm">
              <div>
                <div className="text-zinc-500 mb-1">Must-haves</div>
                <ul className="list-disc pl-5 space-y-1">
                  {(a.must_haves || a.requirements || []).map((r: string, i: number) => (
                    <li key={i}>{r}</li>
                  ))}
                </ul>
              </div>
              <div>
                <div className="text-zinc-500 mb-1">Nice-to-haves</div>
                <ul className="list-disc pl-5 space-y-1">
                  {(a.nice_to_haves || []).map((r: string, i: number) => (
                    <li key={i}>{r}</li>
                  ))}
                  {!(a.nice_to_haves || []).length && <li className="text-zinc-600 list-none -ml-5">None noted</li>}
                </ul>
              </div>
              <div>
                <div className="text-zinc-500 mb-1">Why we match</div>
                <ul className="list-disc pl-5 space-y-1">
                  {(a.reasons || []).map((r: string, i: number) => (
                    <li key={i}>{r}</li>
                  ))}
                </ul>
              </div>
              <div>
                <div className="text-zinc-500 mb-1">Weaknesses / gaps</div>
                <ul className="list-disc pl-5 space-y-1">
                  {(a.weaknesses || []).map((r: string, i: number) => (
                    <li key={i}>{r}</li>
                  ))}
                </ul>
              </div>
            </div>
            {(a.red_flags || []).length > 0 && (
              <div>
                <div className="text-red-400 text-xs uppercase mb-1">Red flags</div>
                <ul className="list-disc pl-5 text-sm text-red-300 space-y-1">
                  {a.red_flags.map((r: string, i: number) => (
                    <li key={i}>{r}</li>
                  ))}
                </ul>
              </div>
            )}
            {(a.portfolio_evidence || []).length > 0 && (
              <div>
                <div className="text-zinc-500 text-xs uppercase mb-2">Portfolio evidence</div>
                <ul className="space-y-2 text-sm">
                  {a.portfolio_evidence.map((p: any, i: number) => (
                    <li key={i} className="border border-zinc-800 rounded-lg p-3">
                      <div className="font-medium">{p.title || "Project"} {p.score != null ? `· ${p.score}` : ""}</div>
                      <div className="text-zinc-400 mt-1">{p.why}</div>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </section>
        ) : (
          <section className="border border-zinc-800 rounded-xl p-5">
            <p className="text-sm text-zinc-400 mb-3">No AI analysis yet.</p>
            <button onClick={reanalyze} disabled={!!busy} className="rounded-lg bg-emerald-500 text-zinc-950 px-4 py-2 font-medium disabled:opacity-50">
              {busy === "analyze" ? "Analyzing…" : "Run deep AI analysis"}
            </button>
          </section>
        )}

        <section className="border border-zinc-800 rounded-xl p-5">
          <h2 className="font-medium mb-2">Full job description</h2>
          <pre className="whitespace-pre-wrap text-sm text-zinc-300 font-sans">{job.description}</pre>
        </section>

        {error && <p className="text-red-400 text-sm">{error}</p>}
        <div className="flex flex-wrap gap-3">
          <button onClick={generate} disabled={!!busy} className="rounded-lg bg-emerald-500 text-zinc-950 px-4 py-2 font-medium disabled:opacity-50">
            {busy === "generate" ? "Generating…" : "Generate 3 proposal strategies"}
          </button>
          <button
            onClick={() => api(`/jobs/${id}/skip`, { method: "POST" }).then(() => router.push("/jobs"))}
            className="rounded-lg border border-zinc-700 px-4 py-2"
          >
            Skip
          </button>
        </div>
      </div>

      <aside className="border border-zinc-800 rounded-xl bg-zinc-950/60 sticky top-6 h-[calc(100vh-4rem)] flex flex-col overflow-hidden">
        <div className="p-4 border-b border-zinc-800">
          <h2 className="font-medium">Proposal chat</h2>
          <p className="text-xs text-zinc-500 mt-1">Ask AI to draft or improve a personalized proposal for this job.</p>
          <div className="flex flex-wrap gap-2 mt-3">
            {[
              "Draft a personalized proposal using our best matching projects",
              "Improve this for a more problem-first opening",
              "Make it shorter and more confident",
              "Write a technical approach section for this job",
            ].map((q) => (
              <button
                key={q}
                disabled={!!busy}
                onClick={() => sendChat(undefined, q)}
                className="text-[11px] rounded-full border border-zinc-700 px-2.5 py-1 text-zinc-300 hover:border-emerald-500/50 disabled:opacity-50"
              >
                {q.length > 42 ? q.slice(0, 42) + "…" : q}
              </button>
            ))}
          </div>
        </div>
        <div className="flex-1 overflow-y-auto p-4 space-y-3">
          {!chat.length && (
            <p className="text-sm text-zinc-500">
              Example: “Write a proposal referencing Colala Mall / FEDCanada style work and our Laravel SaaS experience.”
            </p>
          )}
          {chat.map((m) => (
            <div
              key={m.id}
              className={`rounded-xl px-3 py-2 text-sm whitespace-pre-wrap ${
                m.role === "user" ? "bg-emerald-500/15 ml-6" : "bg-zinc-900 mr-2 border border-zinc-800"
              }`}
            >
              <div className="text-[10px] uppercase tracking-wide text-zinc-500 mb-1">{m.role}</div>
              {m.content}
            </div>
          ))}
          <div ref={chatEnd} />
        </div>
        <form onSubmit={sendChat} className="p-3 border-t border-zinc-800 flex gap-2">
          <textarea
            value={message}
            onChange={(e) => setMessage(e.target.value)}
            rows={2}
            placeholder="Ask to generate or improve a proposal…"
            className="flex-1 bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-sm resize-none"
          />
          <button
            type="submit"
            disabled={!!busy || !message.trim()}
            className="self-end rounded-lg bg-emerald-500 text-zinc-950 px-4 py-2 text-sm font-medium disabled:opacity-50"
          >
            {busy === "chat" ? "…" : "Send"}
          </button>
        </form>
      </aside>
    </div>
  );
}

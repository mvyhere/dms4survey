import { internalMutation, internalQuery } from "./_generated/server";
import { v } from "convex/values";

export const createSession = internalMutation({
  args: {
    token: v.string(),
    createdAt: v.number(),
    expiresAt: v.number(),
  },
  handler: async (ctx, args) => {
    await ctx.db.insert("adminSessions", args);
    return { ok: true };
  },
});

export const getSessionByToken = internalQuery({
  args: {
    token: v.string(),
  },
  handler: async (ctx, args) => {
    return await ctx.db
      .query("adminSessions")
      .withIndex("by_token", (q) => q.eq("token", args.token))
      .unique();
  },
});

export const deleteSessionByToken = internalMutation({
  args: {
    token: v.string(),
  },
  handler: async (ctx, args) => {
    const session = await ctx.db
      .query("adminSessions")
      .withIndex("by_token", (q) => q.eq("token", args.token))
      .unique();

    if (session) {
      await ctx.db.delete(session._id);
    }

    return { ok: true };
  },
});

export const deleteExpiredSessions = internalMutation({
  args: {
    now: v.number(),
  },
  handler: async (ctx, args) => {
    const expired = await ctx.db
      .query("adminSessions")
      .withIndex("by_expires_at", (q) => q.lte("expiresAt", args.now))
      .collect();

    for (const session of expired) {
      await ctx.db.delete(session._id);
    }

    return { deleted: expired.length };
  },
});

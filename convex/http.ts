import { httpRouter } from "convex/server";
import { httpAction } from "./_generated/server";
import { internal } from "./_generated/api";

const http = httpRouter();
const ONE_WEEK_MS = 7 * 24 * 60 * 60 * 1000;

function corsHeaders(request: Request) {
  const origin = request.headers.get("origin") || "*";
  return {
    "Access-Control-Allow-Origin": origin,
    "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
    "Access-Control-Allow-Headers": "Content-Type, Authorization",
    "Access-Control-Max-Age": "86400",
    Vary: "Origin",
  };
}

function jsonResponse(request: Request, payload: unknown, status = 200) {
  return new Response(JSON.stringify(payload), {
    status,
    headers: {
      ...corsHeaders(request),
      "Content-Type": "application/json; charset=utf-8",
      "Cache-Control": "no-store",
    },
  });
}

function emptyResponse(request: Request, status = 204) {
  return new Response(null, {
    status,
    headers: corsHeaders(request),
  });
}

function unauthorized(request: Request, message = "Admin login required.") {
  return jsonResponse(request, { success: false, message }, 401);
}

function readBearerToken(request: Request) {
  const authHeader = request.headers.get("authorization") || "";
  if (!authHeader.toLowerCase().startsWith("bearer ")) {
    return "";
  }
  return authHeader.slice(7).trim();
}

async function getValidSession(ctx: any, request: Request) {
  const token = readBearerToken(request);
  if (!token) return null;

  await ctx.runMutation(internal.admin.deleteExpiredSessions, { now: Date.now() });
  const session = await ctx.runQuery(internal.admin.getSessionByToken, { token });
  if (!session) return null;
  if (session.expiresAt <= Date.now()) return null;
  return session;
}

http.route({
  path: "/submitSurvey",
  method: "OPTIONS",
  handler: httpAction(async (_ctx, request) => emptyResponse(request)),
});

http.route({
  path: "/submitSurvey",
  method: "POST",
  handler: httpAction(async (ctx, request) => {
    try {
      const payload = await request.json();
      await ctx.runMutation(internal.survey.submitSurvey, payload);
      return jsonResponse(request, {
        success: true,
        message: "Survey submitted successfully.",
      });
    } catch (error: any) {
      return jsonResponse(
        request,
        {
          success: false,
          message: error?.message || "Unable to submit survey.",
        },
        400,
      );
    }
  }),
});

http.route({
  path: "/adminLogin",
  method: "OPTIONS",
  handler: httpAction(async (_ctx, request) => emptyResponse(request)),
});

http.route({
  path: "/adminLogin",
  method: "POST",
  handler: httpAction(async (ctx, request) => {
    try {
      const payload = await request.json();
      const username = String(payload?.username || "").trim();
      const password = String(payload?.password || "").trim();
      const validUsername = process.env.ADMIN_USERNAME || "admin";
      const validPassword = process.env.ADMIN_PASSWORD || "webarebel";

      if (username !== validUsername || password !== validPassword) {
        return unauthorized(request, "Invalid admin username or password.");
      }

      const token = `${crypto.randomUUID()}${crypto.randomUUID()}`;
      const now = Date.now();
      await ctx.runMutation(internal.admin.deleteExpiredSessions, { now });
      await ctx.runMutation(internal.admin.createSession, {
        token,
        createdAt: now,
        expiresAt: now + ONE_WEEK_MS,
      });

      return jsonResponse(request, {
        success: true,
        message: "Admin login successful.",
        token,
      });
    } catch (error: any) {
      return jsonResponse(
        request,
        {
          success: false,
          message: error?.message || "Login failed.",
        },
        400,
      );
    }
  }),
});

http.route({
  path: "/adminLogout",
  method: "OPTIONS",
  handler: httpAction(async (_ctx, request) => emptyResponse(request)),
});

http.route({
  path: "/adminLogout",
  method: "POST",
  handler: httpAction(async (ctx, request) => {
    const token = readBearerToken(request);
    if (token) {
      await ctx.runMutation(internal.admin.deleteSessionByToken, { token });
    }
    return jsonResponse(request, {
      success: true,
      message: "Logged out successfully.",
    });
  }),
});

http.route({
  path: "/getStats",
  method: "OPTIONS",
  handler: httpAction(async (_ctx, request) => emptyResponse(request)),
});

http.route({
  path: "/getStats",
  method: "GET",
  handler: httpAction(async (ctx, request) => {
    const session = await getValidSession(ctx, request);
    if (!session) {
      return unauthorized(request);
    }

    const stats = await ctx.runQuery(internal.survey.getStats, {});
    return jsonResponse(request, {
      success: true,
      stats,
    });
  }),
});

export default http;

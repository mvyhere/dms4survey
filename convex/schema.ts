import { defineSchema, defineTable } from "convex/server";
import { v } from "convex/values";

export default defineSchema({
  surveyResponses: defineTable({
    respondentName: v.string(),
    genderKey: v.string(),
    genderOther: v.optional(v.string()),
    ageYears: v.number(),
    roleKey: v.string(),
    roleOther: v.optional(v.string()),
    routeKey: v.string(),
    preferredLanguage: v.string(),
    answers: v.any(),
    submittedAt: v.number(),
  })
    .index("by_submitted_at", ["submittedAt"])
    .index("by_route", ["routeKey"])
    .index("by_role", ["roleKey"]),

  adminSessions: defineTable({
    token: v.string(),
    createdAt: v.number(),
    expiresAt: v.number(),
  })
    .index("by_token", ["token"])
    .index("by_expires_at", ["expiresAt"]),
});

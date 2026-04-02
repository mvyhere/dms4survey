import { internalMutation, internalQuery } from "./_generated/server";
import { v } from "convex/values";

function normalizeText(value: unknown, maxLength: number) {
  const text = String(value ?? "").trim();
  return text.length > maxLength ? text.slice(0, maxLength) : text;
}

function roleRoutes() {
  return {
    parent_caregiver: "parent",
    teacher_educator: "teacher",
    therapist_specialist: "teacher",
    family_member: "general",
    friend_acquaintance: "general",
    student_researcher: "general",
    general_interest: "general",
    other: "general",
  } as const;
}

function getRouteFromRole(roleKey: string) {
  const routes = roleRoutes();
  return routes[roleKey as keyof typeof routes] ?? null;
}

function routeMatches(routeConstraint: string | string[], routeKey: string) {
  return Array.isArray(routeConstraint)
    ? routeConstraint.includes(routeKey)
    : routeConstraint === routeKey;
}

function surveySpecs() {
  const frequencyOptions = ["daily", "few_times_week", "occasionally", "rarely", "never"];
  const shiftOptions = ["easy_shift", "slow_shift_needs_prompt", "hard_to_leave_old", "not_sure"];
  const attentionOptions = ["balanced_attention", "slight_detail_bias", "mostly_small_details", "not_sure"];
  const effectivenessOptions = [
    "extremely_effective",
    "very_effective",
    "moderately_effective",
    "slightly_effective",
    "not_effective",
    "not_sure",
  ];
  const scanTimeOptions = ["instant", "under_5_seconds", "under_10_seconds", "under_20_seconds", "wait_for_quality"];
  const trackOptions = [
    "time_focused",
    "pages_completed",
    "vocabulary_mastered",
    "sensory_triggers_identified",
    "preferred_objects_topics",
    "repeated_prompts_needed",
  ];
  const necessityOptions = [
    "extremely_necessary",
    "very_necessary",
    "somewhat_necessary",
    "slightly_necessary",
    "not_necessary",
  ];
  const appUsefulnessOptions = ["extremely_useful", "very_useful", "somewhat_useful", "slightly_useful", "not_useful"];
  const outcomeOptions = [
    "longer_attention",
    "better_understanding",
    "reduced_frustration",
    "easier_shared_reading",
    "better_language",
  ];
  const trustOptions = [
    "custom_sensory_settings",
    "clear_progress_tracking",
    "fast_scan_processing",
    "works_with_existing_books",
    "calm_simple_interface",
    "affordable_practical_pricing",
    "professional_recommendations",
  ];
  const pricingOptions = ["less_than_100k", "vnd_100k_200k", "vnd_200k_500k", "more_than_500k", "only_if_free"];

  return {
    parent_material_frequency: {
      type: "single",
      route: "parent",
      required: true,
      options: frequencyOptions,
    },
    teacher_material_frequency: {
      type: "single",
      route: "teacher",
      required: true,
      options: frequencyOptions,
    },
    teacher_best_book_type: {
      type: "single",
      route: "teacher",
      required: true,
      options: [
        "simple_picture_books",
        "repetitive_predictable_stories",
        "social_stories",
        "sensory_audio_books",
        "digital_interactive_books",
        "depends_on_child",
        "physical_picture_books",
        "not_sure",
      ],
    },
    common_transition_shift: {
      type: "single",
      route: ["parent", "teacher", "general"],
      required: true,
      options: shiftOptions,
    },
    common_attention_style: {
      type: "single",
      route: ["parent", "teacher", "general"],
      required: true,
      options: attentionOptions,
    },
    parent_interactive_books_effectiveness: {
      type: "single",
      route: "parent",
      required: true,
      options: effectivenessOptions,
    },
    parent_scan_time: {
      type: "single",
      route: "parent",
      required: true,
      options: scanTimeOptions,
    },
    parent_track_data: {
      type: "multi",
      route: "parent",
      required: true,
      max: 2,
      options: trackOptions,
    },
    common_necessity: {
      type: "single",
      route: ["parent", "teacher", "general"],
      required: true,
      options: necessityOptions,
    },
    general_app_usefulness: {
      type: "single",
      route: "general",
      required: true,
      options: appUsefulnessOptions,
    },
    common_outcome: {
      type: "single",
      route: ["parent", "teacher", "general"],
      required: true,
      options: outcomeOptions,
    },
    parent_visual_settings_importance: {
      type: "scale",
      route: "parent",
      required: true,
      min: 1,
      max: 5,
    },
    common_trust_factors: {
      type: "multi",
      route: ["parent", "teacher", "general"],
      required: true,
      max: 2,
      options: trustOptions,
    },
    common_monthly_price: {
      type: "single",
      route: ["parent", "teacher", "general"],
      required: true,
      options: pricingOptions,
    },
    parent_open_feedback: {
      type: "text",
      route: "parent",
      required: false,
      max_length: 3000,
    },
    teacher_open_feedback: {
      type: "text",
      route: "teacher",
      required: false,
      max_length: 3000,
    },
    general_open_feedback: {
      type: "text",
      route: "general",
      required: false,
      max_length: 3000,
    },
  } as const;
}

function validateAnswerValue(questionKey: string, spec: any, value: unknown) {
  if (spec.type === "single") {
    const answer = String(value ?? "").trim();
    if (answer === "") return null;
    if (!spec.options.includes(answer)) {
      throw new Error(`Invalid answer for ${questionKey}.`);
    }
    return answer;
  }

  if (spec.type === "multi") {
    if (!Array.isArray(value)) return [];
    const answers: string[] = [];
    for (const item of value) {
      const answer = String(item ?? "").trim();
      if (answer && !answers.includes(answer)) {
        answers.push(answer);
      }
    }
    if (answers.length > spec.max) {
      throw new Error(`Too many selections for ${questionKey}.`);
    }
    for (const answer of answers) {
      if (!spec.options.includes(answer)) {
        throw new Error(`Invalid selection for ${questionKey}.`);
      }
    }
    return answers;
  }

  if (spec.type === "scale") {
    if (value === null || value === undefined || value === "") return null;
    const number = Number(value);
    if (!Number.isFinite(number)) {
      throw new Error(`Scale answer must be numeric for ${questionKey}.`);
    }
    const whole = Math.trunc(number);
    if (whole < spec.min || whole > spec.max) {
      throw new Error(`Scale answer out of range for ${questionKey}.`);
    }
    return whole;
  }

  if (spec.type === "text") {
    const text = normalizeText(value, spec.max_length);
    return text === "" ? null : text;
  }

  throw new Error("Unsupported question type.");
}

function validateSubmission(payload: any) {
  const name = normalizeText(payload.name, 150);
  const gender = normalizeText(payload.gender, 50);
  const genderOther = normalizeText(payload.gender_other, 150);
  const ageRaw = payload.age;
  const role = normalizeText(payload.role, 80);
  const roleOther = normalizeText(payload.role_other, 150);
  const language = ["en", "vi"].includes(payload.language) ? payload.language : "en";
  const answers = payload.answers && typeof payload.answers === "object" ? payload.answers : {};

  if (!name) throw new Error("Name is required.");

  const validGenders = ["male", "female", "prefer_not_say", "other"];
  if (!validGenders.includes(gender)) {
    throw new Error("Please choose a valid gender option.");
  }
  if (gender === "other" && !genderOther) {
    throw new Error("Please specify the other gender option.");
  }

  const age = Number(ageRaw);
  if (!Number.isFinite(age)) {
    throw new Error("Age must be a number.");
  }
  if (age < 1 || age > 120) {
    throw new Error("Age must be between 1 and 120.");
  }

  const route = getRouteFromRole(role);
  if (!route) {
    throw new Error("Please choose a valid relationship option.");
  }
  if (role === "other" && !roleOther) {
    throw new Error("Please specify the other relationship option.");
  }

  const specs = surveySpecs();
  const validatedAnswers: Record<string, any> = {};

  for (const [questionKey, spec] of Object.entries(specs)) {
    if (!routeMatches(spec.route as string | string[], route)) continue;
    const validatedValue = validateAnswerValue(questionKey, spec, answers[questionKey]);
    const isEmpty =
      validatedValue === null ||
      validatedValue === "" ||
      (Array.isArray(validatedValue) && validatedValue.length === 0);

    if (spec.required && isEmpty) {
      throw new Error("Please answer all required questions before submitting.");
    }

    if (!isEmpty) {
      validatedAnswers[questionKey] = validatedValue;
    }
  }

  return {
    name,
    gender,
    genderOther,
    age: Math.trunc(age),
    role,
    roleOther,
    route,
    language,
    answers: validatedAnswers,
  };
}

function buildAgeBuckets() {
  return {
    under_18: 0,
    age_18_24: 0,
    age_25_34: 0,
    age_35_44: 0,
    age_45_plus: 0,
  };
}

function ageBucketKey(age: number) {
  if (age < 18) return "under_18";
  if (age <= 24) return "age_18_24";
  if (age <= 34) return "age_25_34";
  if (age <= 44) return "age_35_44";
  return "age_45_plus";
}

function createQuestionAccumulator(type: string) {
  return {
    type,
    optionCounts: {} as Record<string, number>,
    responseCount: 0,
    selectionCount: 0,
    textResponses: [] as Array<{
      responseId: string;
      name: string;
      route: string;
      submittedAt: number;
      text: string;
    }>,
    average: null as number | null,
    scaleTotal: 0,
    scaleCount: 0,
  };
}

function buildStatsFromResponses(rawResponses: any[]) {
  const responses = [...rawResponses].sort((a, b) => b.submittedAt - a.submittedAt);
  const stats = {
    generatedAt: new Date().toISOString(),
    totalResponses: 0,
    routeCounts: { parent: 0, teacher: 0, general: 0 },
    roleCounts: {} as Record<string, number>,
    genderCounts: {} as Record<string, number>,
    ageSummary: {
      average: 0,
      min: null as number | null,
      max: null as number | null,
      buckets: buildAgeBuckets(),
    },
    submissionsByDate: {} as Record<string, number>,
    questions: {} as Record<string, any>,
    responses: [] as any[],
  };

  const questionRespondents: Record<string, Set<string>> = {};
  const specs = surveySpecs();
  let ageTotal = 0;

  for (const response of responses) {
    stats.totalResponses += 1;
    stats.routeCounts[response.routeKey as keyof typeof stats.routeCounts] =
      (stats.routeCounts[response.routeKey as keyof typeof stats.routeCounts] || 0) + 1;
    stats.roleCounts[response.roleKey] = (stats.roleCounts[response.roleKey] || 0) + 1;
    stats.genderCounts[response.genderKey] = (stats.genderCounts[response.genderKey] || 0) + 1;

    const age = Number(response.ageYears);
    ageTotal += age;
    const bucketKey = ageBucketKey(age);
    stats.ageSummary.buckets[bucketKey as keyof typeof stats.ageSummary.buckets] += 1;
    if (stats.ageSummary.min === null || age < stats.ageSummary.min) stats.ageSummary.min = age;
    if (stats.ageSummary.max === null || age > stats.ageSummary.max) stats.ageSummary.max = age;

    const dateKey = new Date(response.submittedAt).toISOString().slice(0, 10);
    stats.submissionsByDate[dateKey] = (stats.submissionsByDate[dateKey] || 0) + 1;

    const responseOut = {
      id: String(response._id),
      name: response.respondentName,
      genderKey: response.genderKey,
      genderOther: response.genderOther || "",
      age,
      roleKey: response.roleKey,
      roleOther: response.roleOther || "",
      route: response.routeKey,
      language: response.preferredLanguage,
      submittedAt: response.submittedAt,
      answers: response.answers || {},
    };
    stats.responses.push(responseOut);

    const answers = response.answers || {};
    for (const [questionKey, value] of Object.entries(answers)) {
      const spec = specs[questionKey as keyof typeof specs];
      if (!spec) continue;

      if (!stats.questions[questionKey]) {
        stats.questions[questionKey] = createQuestionAccumulator(spec.type);
      }
      if (!questionRespondents[questionKey]) {
        questionRespondents[questionKey] = new Set();
      }
      questionRespondents[questionKey].add(String(response._id));

      if (spec.type === "single") {
        const key = String(value);
        stats.questions[questionKey].optionCounts[key] = (stats.questions[questionKey].optionCounts[key] || 0) + 1;
        continue;
      }

      if (spec.type === "multi") {
        for (const item of Array.isArray(value) ? value : []) {
          const key = String(item);
          stats.questions[questionKey].optionCounts[key] = (stats.questions[questionKey].optionCounts[key] || 0) + 1;
          stats.questions[questionKey].selectionCount += 1;
        }
        continue;
      }

      if (spec.type === "scale") {
        const key = String(value);
        stats.questions[questionKey].optionCounts[key] = (stats.questions[questionKey].optionCounts[key] || 0) + 1;
        stats.questions[questionKey].scaleTotal += Number(value);
        stats.questions[questionKey].scaleCount += 1;
        continue;
      }

      if (spec.type === "text") {
        const text = String(value ?? "").trim();
        if (text) {
          stats.questions[questionKey].textResponses.push({
            responseId: String(response._id),
            name: response.respondentName,
            route: response.routeKey,
            submittedAt: response.submittedAt,
            text,
          });
        }
      }
    }
  }

  if (stats.totalResponses > 0) {
    stats.ageSummary.average = Math.round((ageTotal / stats.totalResponses) * 10) / 10;
  }

  for (const [questionKey, data] of Object.entries(stats.questions)) {
    data.responseCount = questionRespondents[questionKey] ? questionRespondents[questionKey].size : 0;
    if (data.type === "scale" && data.scaleCount > 0) {
      data.average = Math.round((data.scaleTotal / data.scaleCount) * 100) / 100;
    }
    delete data.scaleTotal;
    delete data.scaleCount;
  }

  stats.responses.sort((a, b) => b.submittedAt - a.submittedAt);
  return stats;
}

export const submitSurvey = internalMutation({
  args: {
    name: v.string(),
    gender: v.string(),
    gender_other: v.optional(v.string()),
    age: v.any(),
    role: v.string(),
    role_other: v.optional(v.string()),
    language: v.optional(v.string()),
    answers: v.any(),
  },
  handler: async (ctx, args) => {
    const submission = validateSubmission(args);
    return await ctx.db.insert("surveyResponses", {
      respondentName: submission.name,
      genderKey: submission.gender,
      genderOther: submission.genderOther || undefined,
      ageYears: submission.age,
      roleKey: submission.role,
      roleOther: submission.roleOther || undefined,
      routeKey: submission.route,
      preferredLanguage: submission.language,
      answers: submission.answers,
      submittedAt: Date.now(),
    });
  },
});

export const getStats = internalQuery({
  args: {},
  handler: async (ctx) => {
    const responses = await ctx.db.query("surveyResponses").collect();
    return buildStatsFromResponses(responses);
  },
});

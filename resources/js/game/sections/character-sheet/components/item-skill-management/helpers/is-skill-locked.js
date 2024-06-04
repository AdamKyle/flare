var __values =
    (this && this.__values) ||
    function (o) {
        var s = typeof Symbol === "function" && Symbol.iterator,
            m = s && o[s],
            i = 0;
        if (m) return m.call(o);
        if (o && typeof o.length === "number")
            return {
                next: function () {
                    if (o && i >= o.length) o = void 0;
                    return { value: o && o[i++], done: !o };
                },
            };
        throw new TypeError(
            s ? "Object is not iterable." : "Symbol.iterator is not defined.",
        );
    };
export var isSkillLocked = function (skill, skillData, progressionData) {
    var parentSkill = findParentSkill(skill, skillData);
    var isLocked = false;
    if (typeof parentSkill !== "undefined") {
        var progressionDataForParent = getSkillProgressionData(
            parentSkill,
            progressionData,
        );
        if (
            typeof progressionDataForParent !== "undefined" &&
            skill.parent_level_needed !== null
        ) {
            isLocked =
                progressionDataForParent.current_level <
                skill.parent_level_needed;
        }
    }
    return isLocked;
};
export var getSkillProgressionData = function (skill, progressionData) {
    return progressionData.find(function (data) {
        return data.item_skill_id === skill.id;
    });
};
export var findParentSkill = function (skill, skills) {
    var e_1, _a;
    try {
        for (
            var skills_1 = __values(skills), skills_1_1 = skills_1.next();
            !skills_1_1.done;
            skills_1_1 = skills_1.next()
        ) {
            var skillData = skills_1_1.value;
            if (skillData.id === skill.parent_id) {
                return skillData;
            }
            if (skillData.children.length > 0) {
                var parentSkill = findParentSkill(skill, skillData.children);
                if (typeof parentSkill !== "undefined") {
                    return parentSkill;
                }
            }
        }
    } catch (e_1_1) {
        e_1 = { error: e_1_1 };
    } finally {
        try {
            if (skills_1_1 && !skills_1_1.done && (_a = skills_1.return))
                _a.call(skills_1);
        } finally {
            if (e_1) throw e_1.error;
        }
    }
    return undefined;
};
//# sourceMappingURL=is-skill-locked.js.map

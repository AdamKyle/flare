var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
var __param = (this && this.__param) || function (paramIndex, decorator) {
    return function (target, key) { decorator(target, key, paramIndex); }
};
import { inject, injectable } from "tsyringe";
import Ajax from "../../../../game/lib/ajax/ajax.js";
export var GUIDE_QUEST_ACTIONS;
(function (GUIDE_QUEST_ACTIONS) {
    GUIDE_QUEST_ACTIONS["FETCH"] = "fetch";
    GUIDE_QUEST_ACTIONS["HAND_IN"] = "hand-in";
})(GUIDE_QUEST_ACTIONS || (GUIDE_QUEST_ACTIONS = {}));
var GuideQuestAjax = (function () {
    function GuideQuestAjax(ajax) {
        this.ajax = ajax;
    }
    GuideQuestAjax.prototype.doGuideQuestAction = function (component, actionType, params) {
        var guideQuestId = 0;
        if (component.state.quest_data !== null) {
            guideQuestId = component.state.quest_data.id;
        }
        var route = this.getRoute(actionType, component.props.user_id, guideQuestId);
        var actionForRoute = this.getActionType(actionType);
        if (actionType === GUIDE_QUEST_ACTIONS.FETCH) {
            return this.handleFetchGuideQuest(component, route, actionForRoute, params);
        }
        this.handleHandingInGuideQuest(component, route, actionForRoute, params);
    };
    GuideQuestAjax.prototype.handleFetchGuideQuest = function (component, route, action, params) {
        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(action, function (result) {
            component.setState({
                loading: false,
                quest_data: result.data.quest,
                can_hand_in: result.data.can_hand_in,
                completed_requirements: result.data.completed_requirements,
            });
        }, function (error) {
            component.setState({
                loading: false,
            });
            if (typeof error.response !== "undefined") {
                var response = error.response;
                component.setState({
                    error_message: response.data.message,
                });
            }
        });
    };
    GuideQuestAjax.prototype.handleHandingInGuideQuest = function (component, route, action, params) {
        component.setState({ is_handing_in: true });
        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(action, function (result) {
            component.setState({
                is_handing_in: false,
                quest_data: result.data.quest,
                can_hand_in: result.data.can_hand_in,
                success_message: result.data.message,
                completed_requirements: result.data.completed_requirements,
            });
        }, function (error) {
            component.setState({
                is_handing_in: false,
            });
            if (typeof error.response !== "undefined") {
                var response = error.response;
                component.setState({
                    error_message: response.data.message,
                });
            }
        });
    };
    GuideQuestAjax.prototype.getRoute = function (actionType, userId, guideQuestId) {
        switch (actionType) {
            case GUIDE_QUEST_ACTIONS.FETCH:
                return "character/guide-quest/" + userId;
            case GUIDE_QUEST_ACTIONS.HAND_IN:
                return "guide-quests/hand-in/" + userId + "/" + guideQuestId;
            default:
                throw new Error("Unknown route to take.");
        }
    };
    GuideQuestAjax.prototype.getActionType = function (actionType) {
        switch (actionType) {
            case GUIDE_QUEST_ACTIONS.FETCH:
                return "get";
            case GUIDE_QUEST_ACTIONS.HAND_IN:
                return "post";
            default:
                throw new Error("Unknown action to take for route.");
        }
    };
    GuideQuestAjax = __decorate([
        injectable(),
        __param(0, inject(Ajax)),
        __metadata("design:paramtypes", [Object])
    ], GuideQuestAjax);
    return GuideQuestAjax;
}());
export default GuideQuestAjax;
//# sourceMappingURL=guide-quest-ajax.js.map
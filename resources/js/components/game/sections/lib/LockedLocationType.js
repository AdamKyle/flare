export default class LockedLocationType {

  static get PURGATORYSMITHSHOUSE() {
    return 0;
  }

  static getEffect(level) {

    switch (level) {
      case 1:
        return {
          'devouring_darkness_range': '1-3%',
          'stat_bonus_increase': '1-3%'
        }
      case 2:
        return {
          'devouring_darkness_range': '1-8%',
          'stat_bonus_increase': '1-8%'
        }
      case 3:
        return {
          'devouring_darkness_range': '1-15%',
          'stat_bonus_increase': '1-15%'
        }
      case 4:
        return {
          'devouring_darkness_range': '1-25%',
          'stat_bonus_increase': '1-25%'
        }
      case 5:
        return {
          'devouring_darkness_range': '1-35%',
          'stat_bonus_increase': '1-35%'
        }
      default:
        return {
          'devouring_darkness_range': 'ERROR.',
          'stat_bonus_increase': 'ERROR.'
        }
    }
  }
}

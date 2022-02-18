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
          'devouring_darkness_range': '1-5%',
          'stat_bonus_increase': '1-5%'
        }
      case 3:
        return {
          'devouring_darkness_range': '1-8%',
          'stat_bonus_increase': '1-8%'
        }
      case 4:
        return {
          'devouring_darkness_range': '1-10%',
          'stat_bonus_increase': '1-10%'
        }
      case 5:
        return {
          'devouring_darkness_range': '1-15%',
          'stat_bonus_increase': '1-15%'
        }
      default:
        return {
          'devouring_darkness_range': 'ERROR.',
          'stat_bonus_increase': 'ERROR.'
        }
    }
  }
}
